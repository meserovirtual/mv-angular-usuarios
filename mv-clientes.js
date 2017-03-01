(function () {
    'use strict';

    var scripts = document.getElementsByTagName("script");
    var currentScriptPath = scripts[scripts.length - 1].src;

    if (currentScriptPath.length == 0) {
        currentScriptPath = window.installPath + '/mv-angular-usuarios/includes/mv-usuarios.php';
    }

    angular.module('mvUsuarios', [])
        .config(function Config($httpProvider, jwtInterceptorProvider) {
            jwtInterceptorProvider.tokenGetter = [function () {
                return localStorage.getItem(window.app);
            }];

            $httpProvider.interceptors.push('jwtInterceptor');
        })
        .run(function ($rootScope, jwtHelper, $location, UserVars) {
            // Para activar la seguridad en una vista, agregar data:{requiresLogin:false} dentro de $routeProvider.when


            $rootScope.$on('$routeChangeStart', function (e, to) {
                if (to && to.data && to.data.requiresLogin) {
                    if (!localStorage.getItem(window.app)) {
                        e.preventDefault();
                        $location.path(UserVars.loginPath);
                    }
                }
            });
        })
        .factory('UserService', UserService)
        .service('UserVars', UserVars)
        .component('usuarioLogin', usuarioLogin())
        .component('usuarioLogout', usuarioLogout())
    ;

    function usuarioLogin() {
        return {
            bindings: {
                'showSucursales': '<',
                'showCajas': '<',
                'redirect': '=',
                'social': '<',
                'register': '<'
            },
            templateUrl: window.installPath + '/mv-angular-usuarios/mv-clientes-login.html',
            controller: MvLoginController
        }
    }

    MvLoginController.$inject = ["UserService", '$location', '$rootScope'];
    /**
     * @param UserService
     * @param $location
     * @constructor
     */
    function MvLoginController(UserService, $location, $rootScope) {
        var vm = this;
        vm.email = '';
        vm.password = '';
        vm.dir = (vm.redirect == undefined) ? '/' : vm.redirect;
        vm.login = login;
        vm.loginFacebook = loginFacebook;
        vm.loginGoogle = loginGoogle;


        function login() {
            UserService.login(vm.email, vm.password).then(function (data) {
                if (data.status == 200) {
                    $rootScope.$broadcast('login-success');
                    $location.path(vm.dir);
                } else {
                    $rootScope.$broadcast('login-error');
                }
            });
        }

        function loginFacebook() {
            UserService.loginFacebook(function (data) {
                $location.path(vm.dir);
            })
        }

        function loginGoogle() {
            UserService.loginGoogle(function (data) {
                $location.path(vm.dir);
            })
        }


    }

    function usuarioLogout() {
        return {
            bindings: {
                'redirect': '='
            },
            //template: '<button class="mv-usuarios-logout" ng-click="$ctrl.logout()">{{"LOGOUT"|xlat}}</button>',
            template: '<img class="btn-img" style="margin: 5px;" src="images/logout.ico" ng-click="$ctrl.logout()" width="30" height="30">',
            controller: MvLogoutController
        }
    }

    MvLogoutController.$inject = ["UserService", '$rootScope', '$timeout'];
    /**
     * @param $scope
     * @constructor
     */
    function MvLogoutController(UserService, $rootScope, $timeout) {
        var vm = this;
        $timeout(function () {
            vm.dir = (vm.redirect == undefined) ? '/logout' : vm.redirect;
            vm.logout = logout;
            function logout() {
                UserService.logout(vm.dir).then(function (data) {
                    $rootScope.$broadcast('login-error');
                    console.log(data);
                });
            }
        }, 100);

    }


    UserService.$inject = ['$http', 'UserVars', '$cacheFactory', 'MvUtils', 'jwtHelper', 'auth', 'ErrorHandler', '$q', '$location', 'MvUtilsGlobals'];
    function UserService($http, UserVars, $cacheFactory, MvUtils, jwtHelper, auth, ErrorHandler, $q, $location, MvUtilsGlobals) {
        //Variables
        var service = {};

        var url = currentScriptPath.replace('mv-usuarios.js', '/includes/mv-usuarios.php');

        //Function declarations
        service.getLogged = getLogged;
        service.getFromToken = getFromToken;
        service.setLogged = setLogged;
        service.checkLastLogin = checkLastLogin;
        service.generateSession = generateSession;
        service.getDataFromToken = getDataFromToken;
        service.verifyTokenExp = verifyTokenExp;

        service.create = create;
        service.createFromSocial = createFromSocial;
        service.remove = remove;
        service.update = update;


        service.get = get;
        service.save = save;
        service.getDeudores = getDeudores;
        service.getDeudorById = getDeudorById;
        service.getById = getById;
        service.getByParams = getByParams;


        service.login = login;
        service.loginSocial = loginSocial;
        service.loginFacebook = loginFacebook;
        service.loginGoogle = loginGoogle;
        service.logout = logout;

        service.userExist = userExist;
        service.forgotPassword = forgotPassword;
        service.changePassword = changePassword;


        return service;

        //Functions
        /**
         * Función que determina si es un update o un create
         * @param usuario
         * @returns {*}
         */
        function save(usuario) {

            var deferred = $q.defer();

            if (usuario.usuario_id != undefined) {
                deferred.resolve(update(usuario));
            } else {
                deferred.resolve(create(usuario));
            }
            return deferred.promise;
        }


        function verifyTokenExp(){

            var globals = localStorage.getItem(window.app);


        }

        function getDataFromToken(field){

            var globals = localStorage.getItem(window.app);

            if (globals !== undefined && globals !== null) {
                if(field == undefined){
                    return (jwtHelper.decodeToken(globals)).data;
                }else{
                    return (jwtHelper.decodeToken(globals)).data[field];
                }
            } else {
                logout('/login');
                return false;
            }
        }


        /**
         * @description Obtiene un deudor espec�fico
         * @param id
         * @param callback
         */
        function getDeudorById(id, callback) {
            getDeudores(function (data) {
                var response = data.filter(function (elem, index, array) {
                    return id = elem.usuario_id;
                })[0];

                callback(response);
            })
        }

        /**
         * Obtiene todo los deudores
         * @param callback
         * @returns {*}
         */
        function getDeudores() {
            return $http.post(url, {'function': 'getDeudores'})
                .then(function (response) {
                    UserVars.clearCache = false;
                    UserVars.paginas = (response.data.length % UserVars.paginacion == 0) ? parseInt(response.data.length / UserVars.paginacion) : parseInt(response.data.length / UserVars.paginacion) + 1;
                    return response;
                })
                .catch(function (response) {
                    ErrorHandler(response);
                });
        }
        /**
         * Obtiene todo los deudores
         * @param mesa_id
         * @returns {*}
         */
        function generateSession(mesa_id) {
            return $http.post(url, {'function': 'generateSession', 'mesa_id': mesa_id})
                .then(function (response) {
                    localStorage.setItem(window.app, response.data);
                    // response.data.status = response.status;
                    return response.data;
                })
                .catch(function (response) {
                    ErrorHandler(response);
                });
        }


        /**
         *
         * @description Retorna la lista filtrada de productos
         * @param params -> String, separado por comas (,) que contiene la lista de par�metros de b�squeda, por ej: nombre, sku, tienen que ser el mismo nombre que en la base
         * @param values -> termino a buscar
         * @param exact_match -> true, busca la palabra exacta, false, busca si el termino aparece
         */
        function getByParams(params, values, exact_match) {
            return get().then(function (data) {
                return MvUtils.getByParams(params, values, exact_match, data);
            }).then(function (data) {
                return data;
            });
        }


        /** @name: remove
         * @param usuario_id, callback
         * @description: Elimina el usuario seleccionado.
         */
        function remove(usuario_id, callback) {
            return $http.post(url,
                {'function': 'remove', 'usuario_id': usuario_id})
                .success(function (data) {
                    //console.log(data);
                    if (data !== 'false') {
                        UserVars.clearCache = true;
                        callback(data);
                    }
                })
                .error(function (data) {
                    callback(data);
                })
        }

        /** @name: get
         * @param callback
         * @description: Retorna todos los usuario de la base.
         */
        function get(rol_id) {
            MvUtilsGlobals.startWaiting();
            //var urlGet = url + '?function=get';
            //var urlGet = url + '?function=get&all=' + UserVars.all;
            var urlGet = url + '?function=get&rol_id=' + rol_id;
            var $httpDefaultCache = $cacheFactory.get('$http');
            var cachedData = [];

            // Verifica si existe el cache de usuarios
            if ($httpDefaultCache.get(urlGet) != undefined) {
                if (UserVars.clearCache) {
                    $httpDefaultCache.remove(urlGet);
                }
                else {
                    var deferred = $q.defer();
                    cachedData = $httpDefaultCache.get(urlGet);
                    deferred.resolve(cachedData);
                    MvUtilsGlobals.stopWaiting();
                    return deferred.promise;
                }
            }


            return $http.get(urlGet, {cache: true})
                .then(function (response) {

                    /*
                     for (var i = 0; i < response.data.length; i++) {
                     response.data[i].tipo_doc = '' + response.data[i].tipo_doc;
                     }
                     */

                    $httpDefaultCache.put(urlGet, response.data);
                    UserVars.clearCache = false;
                    UserVars.paginas = (response.data.length % UserVars.paginacion == 0) ? parseInt(response.data.length / UserVars.paginacion) : parseInt(response.data.length / UserVars.paginacion) + 1;
                    MvUtilsGlobals.stopWaiting();
                    return response.data;
                })
                .catch(function (response) {
                    MvUtilsGlobals.stopWaiting();
                    ErrorHandler(response);
                });

        }

        /** @name: getById
         * @param id
         * @param callback
         * @description: Retorna el usuario que tenga el id enviado.
         */
        function getById(id, callback) {
            get(function (data) {
                var response = data.filter(function (elem, index, array) {
                    return elem.usuario_id == id;
                })[0];
                callback(response);
            });
        }

        /**
         * todo: Hay que definir si vale la pena
         */
        function checkLastLogin() {

        }


        /** @name: userExist
         * @param mail
         * @description: Verifica que el mail no exista en la base.
         */
        function userExist(mail, callback) {
            return $http.post(url,
                {'function': 'userExist', 'mail': mail})
                .success(function (data) {
                    callback(data);
                })
                .error(function (data) {
                })
        }

        /**
         * Realiza logout
         */
        function logout(path) {
            localStorage.removeItem(window.app);
            return $http.post(url,
                {
                    'function': 'logout'
                })
                .then(function (response) {

                    $location.path(path);
                    return response.data;
                })
                .catch(function (response) {
                    ErrorHandler(response);
                })
        }


        /**
         *
         * @description: realiza login
         * @param mail
         * @param password
         * @param sucursal_id
         * @param caja_id
         * @returns {*}
         */
        function login(mail, password) {

            return $http.post(url,
                {
                    'function': 'loginCliente',
                    'mail': mail,
                    'password': password
                })
                .then(function (response) {
                    localStorage.setItem(window.app, response.data.token);
                    response.data.status = response.status;
                    return response.data;
                })
                .catch(function (response) {
                    console.log(response);
                    ErrorHandler(response);
                    return response;
                })
        }

        /**
         * @description Login directo a partir de los datos obtenidos socialamente
         * @param user
         * @param token
         */
        function loginSocial(user, token) {
            $http.post(url, {'function': 'loginSocial', 'token': token, 'user': JSON.stringify(user)})
                .success(function (data) {
                    if (data != -1) {
                        localStorage.setItem(window.app, data.token);
                    }
                    callback_social(data);
                })
                .error(function (data) {
                    callback_social(data);
                })

        }


        /**
         * @description function intermedia para poder seguir utilizando el callback del llamador en loginSocial
         * @param data
         */
        var callback_social = function (data) {
        };


        /**
         * @description Login para facebook
         * @param callback
         */
        function loginFacebook(callback) {

            callback_social = callback;
            auth.signin({
                popup: true,
                connections: ['facebook'],
                scope: 'openid name email'
            }, onLoginSuccess, onLoginFailed);
        }


        /**
         * @description Login para gmail
         * @param callback
         */
        function loginGoogle(callback) {

            callback_social = callback;
            auth.signin({
                popup: true,
                connections: ['google-oauth2'],
                scope: 'openid name email'
            }, onLoginSuccess, onLoginFailed);
        }

        /**
         * @description Callback para la respuesta positiva del login con face o gmail
         * @param profile
         * @param token
         */
        function onLoginSuccess(profile, token) {
            userExist(profile.email, function (data) {
                if (data > 0) {
                    var user = {
                        mail: profile.email
                    };
                    $http.post(url, {'function': 'loginSocial', 'token': token, 'user': JSON.stringify(user)})
                        .success(function (data) {
                            if (data != -1) {
                                localStorage.setItem(window.app, data.token);
                            }
                            callback_social(data);
                        })
                        .error(function (data) {
                            callback_social(data);
                        })
                } else {
                    // El usuario no existe, lo mando a creación y asigno lo que me devolvió el login
                    UserVars.user_social = profile;
                    UserVars.token_social = token;
                    callback_social(data);

                }
            });

        }


        /**
         * @description Callback para la respuesta negativa del login con face o gmail
         * @param profile
         * @param token
         */
        function onLoginFailed(data) {
            callback_social(data);
            //$scope.message.text = 'invalid credentials';
        }


        /**
         * @description Crea un usuario a partir de los datos sociales
         * @param usuario
         * @param callback
         * @returns {*}
         */
        function createFromSocial(usuario) {
            return $http.post(url,
                {
                    'function': 'create',
                    'user': JSON.stringify(usuario)
                })
                .then(function (response) {
                    UserVars.clearCache = true;
                    return response.data;
                })
                .error(function (response) {
                    UserVars.clearCache = true;
                    ErrorHandler(response.data);
                });
        }

        /**
         * @description: Crea un usuario.
         * @param usuario
         * @param callback
         * @returns {*}
         */
        function create(usuario) {
            return $http.post(url,
                {
                    'function': 'create',
                    'user': JSON.stringify(usuario)
                })
                .then(function (response) {
                    UserVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    UserVars.clearCache = true;
                    ErrorHandler(response);
                });
        }

        /** @name: getLogged
         * @description: Retorna si existe una cookie de usuario.
         */
        function getLogged() {
            //var globals = $cookieStore.get(window.appName);
            //
            //if (globals !== undefined) {
            //    return globals;
            //} else {
            //    return false;
            //}
        }


        /** @name: getFromToken
         * @description: Retorna si existe un token de usuario.
         */
        function getFromToken() {

            var globals = localStorage.getItem(window.app);

            if (globals !== undefined && globals !== null) {
                return jwtHelper.decodeToken(globals);
            } else {
                logout('/login');
                return false;
            }
        }

        /** @name: setLogged
         * @param user
         * @description: Setea al usuario en una cookie. No est� agregado al login ya que no en todos los casos se necesita cookie.
         */
        function setLogged(user) {
            //$cookieStore.put(window.appName, user);
        }

        /**
         * @description Cambia una contrase�a
         * @param usuario_id
         * @param pass_old
         * @param pass_new
         * @param callback
         * @returns {*}
         */
        function changePassword(usuario_id, pass_old, pass_new, callback) {
            return $http.post(url,
                {
                    'function': 'changePassword',
                    usuario_id: usuario_id,
                    pass_old: pass_old,
                    pass_new: pass_new
                })
                .success(function (data) {
                    UserVars.clearCache = true;
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                })
        }


        /** @name: update
         * @param usuario
         * @description: Realiza update al usuario.
         */
        function update(usuario) {
            return $http.post(url,
                {
                    'function': 'update',
                    'user': JSON.stringify(usuario)
                })
                .then(function (response) {
                    UserVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    UserVars.clearCache = true;
                    ErrorHandler(response);
                });
        }


        /** @name: forgotPassword
         * @param email
         * @description: Genera y reenvia el pass al usuario.
         */
        function forgotPassword(email, callback) {
            return $http.post(url,
                {
                    'function': 'forgotPassword',
                    'email': email
                })
                .success(function (data) {
                    callback(data);
                })
                .error(function (data) {
                    callback(data);
                });
        }


    }


    UserVars.$inject = [];
    /**
     *
     * @constructor
     */
    function UserVars() {
        // Cantidad de p�ginas total del recordset
        this.paginas = 1;
        // P�gina seleccionada
        this.pagina = 1;
        // Cantidad de registros por p�gina
        this.paginacion = 10;
        // Registro inicial, no es p�gina, es el registro
        this.start = 0;

        // Usuario temporal Social
        this.user_social = {};
        this.token_social = '';

        // Indica si debe traer todos los usuarios o solo los activos, por defecto, solo activos
        this.all = false;
        // Indica si se debe limpiar el cach� la pr�xima vez que se solicite un get
        this.clearCache = true;

        // Path al login
        this.loginPath = '/login';
    }

})();