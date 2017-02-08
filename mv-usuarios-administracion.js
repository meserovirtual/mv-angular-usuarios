(function () {
    'use strict';

    angular.module('mvUsuariosAdministracion', [])
        .component('mvUsuariosAdministracion', mvUsuariosAdministracion());

    function mvUsuariosAdministracion() {
        return {
            bindings: {
                searchFunction: '&'
            },
            templateUrl: window.installPath + '/mv-angular-usuarios/mv-usuarios-administracion.html',
            controller: MvUsuariosController
        }
    }

    MvUsuariosController.$inject = ["UserVars", 'UserService', "MvUtils"];
    /**
     * @param AcUsuarios
     * @constructor
     */
    function MvUsuariosController(UserVars, UserService, MvUtils) {
        var vm = this;

        vm.usuarios = [];
        vm.usuario = {};
        vm.status = true;
        vm.repeat_password = '';
        vm.detailsOpen = false;
        vm.update = false;

        vm.save = save;
        vm.cancel = cancel;
        vm.setData = setData;
        vm.loadUsuarios = loadUsuarios;
        vm.remove = remove;
        vm.getPerfil = getPerfil;
        vm.cleanUsuario = cleanUsuario;


        var element1 = angular.element(document.getElementById('apellido'));
        var element2 = angular.element(document.getElementById('nombre'));
        var element3 = angular.element(document.getElementById('password'));
        var element4 = angular.element(document.getElementById('repeat_password'));
        var element5 = angular.element(document.getElementById('email'));
        var element6 = angular.element(document.getElementById('telefono'));

        element1[0].addEventListener('focus', function () {
            element1[0].classList.remove('error-input');
            element1[0].removeEventListener('focus', removeFocus);
        });

        element2[0].addEventListener('focus', function () {
            element2[0].classList.remove('error-input');
            element2[0].removeEventListener('focus', removeFocus);
        });

        element3[0].addEventListener('focus', function () {
            element3[0].classList.remove('error-input');
            element3[0].removeEventListener('focus', removeFocus);
        });

        element4[0].addEventListener('focus', function () {
            element4[0].classList.remove('error-input');
            element4[0].removeEventListener('focus', removeFocus);
        });

        element5[0].addEventListener('focus', function () {
            element5[0].classList.remove('error-input');
            element5[0].removeEventListener('focus', removeFocus);
        });

        element6[0].addEventListener('focus', function () {
            element6[0].classList.remove('error-input');
            element6[0].removeEventListener('focus', removeFocus);
        });


        function removeFocus() { }

        loadUsuarios();

        function loadUsuarios() {
            UserVars.all = true;
            UserService.get('0,1').then(function (data) {
                console.log(data);
                setData(data);
            });
        }


        function save() {
            console.log(vm.usuario);

            if(vm.usuario.apellido === undefined || vm.usuario.apellido.length == 0) {
                element1[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El apellido obligatorio');
                return;
            }
            if(vm.usuario.nombre === undefined || vm.usuario.nombre.length == 0) {
                element2[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El nombre es obligatorio');
                return;
            }
            if(vm.usuario.password === undefined || vm.usuario.password.length == 0){
                element3[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El contraseña es obligatoria');
                return;
            }
            if(vm.repeat_password === undefined || vm.repeat_password.length == 0){
                element4[0].classList.add('error-input');
                MvUtils.showMessage('error', 'Debe repetir la contraseña');
                return;
            }
            if(vm.usuario.password != vm.repeat_password){
                element3[0].classList.add('error-input');
                element4[0].classList.add('error-input');
                MvUtils.showMessage('error', 'Las contraseñas deben ser iguales');
                return;
            }
            if(vm.usuario.mail === undefined || vm.usuario.mail.length == 0) {
                element5[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El mail es obligatorio');
                return;
            } else if(!MvUtils.validateEmail(vm.usuario.mail)) {
                element5[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El mail no tiene un formato correcto');
                return;
            }
            if(vm.usuario.telefono === undefined || vm.usuario.telefono.length == 0) {
                element6[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El teléfono es obligatorio');
                return;
            } else if(!MvUtils.validaTelefono(vm.usuario.telefono)) {
                element6[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El formato del teléfono no es correcto');
                return;
            }

            if (vm.usuario.usuario_id == undefined) {
                vm.usuario.status = 1;
            } else {
                vm.usuario.status = vm.status ? 1 : 0;
            }
            UserService.save(vm.usuario).then(function (data) {
                console.log(data);
                //vm.detailsOpen = (data === undefined || data < 0) ? true : false;
                vm.detailsOpen = data.error;

                if(data.error) {
                    element1[0].classList.add('error-input');
                    element2[0].classList.add('error-input');
                    element3[0].classList.add('error-input');
                    element4[0].classList.add('error-input');
                    element5[0].classList.add('error-input');
                    element6[0].classList.add('error-input');
                    MvUtils.showMessage('error', data.message);
                }
                else {
                    cleanUsuario();
                    loadUsuarios();
                    element1[0].classList.remove('error-input');
                    element2[0].classList.remove('error-input');
                    element3[0].classList.remove('error-input');
                    element4[0].classList.remove('error-input');
                    element5[0].classList.remove('error-input');
                    element6[0].classList.remove('error-input');
                    MvUtils.showMessage('success', data.message);
                }
            }).catch(function (data) {
                vm.usuario = {};
                vm.detailsOpen = true;
            });

        }

        function setData(data) {
            vm.usuarios = data;
            vm.paginas = UserVars.paginas;
        }

        function remove() {
            if(vm.usuario.usuario_id == undefined) {
                alert('Debe seleccionar un Cliente');
            } else {
                var result = confirm('¿Esta seguro que desea eliminar al usuario seleccionado?');
                if(result) {
                    UserService.remove(vm.usuario.usuario_id, function(data){
                        vm.detailsOpen = false;
                        cleanUsuario();
                        loadUsuarios();
                        MvUtils.showMessage('success', 'La registro se borro satisfactoriamente');
                    });
                }
            }
        }

        function cancel() {
            element1[0].classList.remove('error-input');
            element2[0].classList.remove('error-input');
            element3[0].classList.remove('error-input');
            element4[0].classList.remove('error-input');
            element5[0].classList.remove('error-input');
            element6[0].classList.remove('error-input');
            vm.usuarios = [];
            vm.detailsOpen = false;
            UserVars.clearCache = true;
            cleanUsuario();
            loadUsuarios();
        }

        function getPerfil(rol_id) {
            var perfil = '';
            if(rol_id == 0) {
                perfil = 'Administrador';
            } else if(rol_id == 1) {
                perfil = 'Usuarios';
            } else if(rol_id == 2) {
                perfil = 'Proveedor';
            } else if(rol_id == 3) {
                perfil = 'Clientes';
            }
            return perfil;
        }

        function cleanUsuario() {
            vm.usuario = {};
            vm.repeat_password = '';
            vm.status = false;
            vm.update = false;
        }

        // Implementación de la paginación
        vm.start = 0;
        vm.limit = UserVars.paginacion;
        vm.pagina = UserVars.pagina;
        vm.paginas = UserVars.paginas;

        function paginar(vars) {
            if (vars == {}) {
                return;
            }
            vm.start = vars.start;
            vm.pagina = vars.pagina;
        }

        vm.next = function () {
            paginar(MvUtils.next(UserVars));
        };
        vm.prev = function () {
            paginar(MvUtils.prev(UserVars));
        };
        vm.first = function () {
            paginar(MvUtils.first(UserVars));
        };
        vm.last = function () {
            paginar(MvUtils.last(UserVars));
        };

        vm.goToPagina = function () {
            paginar(MvUtils.goToPagina(vm.pagina, UserVars));
        }

    }


})();
