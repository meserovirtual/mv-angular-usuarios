(function () {
    'use strict';

    angular.module('mvProveedoresAdministracion', [])
        .component('mvProveedoresAdministracion', mvProveedoresAdministracion());

    function mvProveedoresAdministracion() {
        return {
            bindings: {
                searchFunction: '&'
            },
            templateUrl: window.installPath + '/mv-angular-usuarios/mv-proveedores-administracion.html',
            controller: MvProveedoresController
        }
    }

    MvProveedoresController.$inject = ["UserVars", 'UserService', "MvUtils"];
    /**
     * @param AcUsuarios
     * @constructor
     */
    function MvProveedoresController(UserVars, UserService, MvUtils) {
        var vm = this;

        vm.usuarios = [];
        vm.usuario = {};
        vm.status = true;
        vm.detailsOpen = false;
        vm.update = false;

        vm.save = save;
        vm.cancel = cancel;
        vm.setData = setData;
        vm.loadUsuarios = loadUsuarios;
        vm.remove = remove;
        vm.cleanUsuario = cleanUsuario;


        var element1 = angular.element(document.getElementById('nombre'));
        var element2 = angular.element(document.getElementById('telefono'));
        var element3 = angular.element(document.getElementById('nro_doc'));
        var element4 = angular.element(document.getElementById('email'));
        var element5 = angular.element(document.getElementById('direccion'));
        var element6 = angular.element(document.getElementById('dir_nro'));

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
            UserService.get(2).then(function (data) {
                console.log(data);
                setData(data);
            });
        }

        function getFechaNacimiento(fechaNacimiento) {
            var dia = fechaNacimiento.substring(0,2);
            var mes = fechaNacimiento.substring(3,5);
            var anio = fechaNacimiento.substring(6,10);
            var date = new Date(anio, mes-1, dia);

            return date;
        }

        function save() {
            if(vm.usuario.nombre === undefined || vm.usuario.nombre.length == 0) {
                element1[0].classList.add('error-input');
                MvUtils.showMessage('error', 'La Razón Social es obligatoria');
                return;
            }
            if(vm.usuario.telefono === undefined || vm.usuario.telefono.length == 0) {
                element2[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El teléfono es obligatorio');
                return;
            } else if(!MvUtils.validaTelefono(vm.usuario.telefono)) {
                element2[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El formato del teléfono no es correcto');
                return;
            }
            if(vm.usuario.mail === undefined || vm.usuario.mail.length == 0) {
                element4[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El mail es obligatorio');
                return;
            } else if(!MvUtils.validateEmail(vm.usuario.mail)) {
                element4[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El mail no tiene un formato correcto');
                return;
            }
            if(vm.usuario.nro_doc === undefined || vm.usuario.nro_doc.length == 0) {
                element3[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El CUIT es obligatorio');
                return;
            } else if(!MvUtils.validaNumero(vm.usuario.nro_doc)){
                element3[0].classList.add('error-input');
                MvUtils.showMessage('error', 'Por favor ingrese solo números en CUIT');
                return;
            } else if(!MvUtils.validaCuit(vm.usuario.nro_doc)) {
                element3[0].classList.add('error-input');
                MvUtils.showMessage('error', 'El CUIT no tiene un formato correcto');
                return;
            } else {
                element3[0].classList.remove('error-input');
            }

            if(vm.usuario.direcciones != undefined) {
                if(vm.usuario.direcciones[0].calle.length > 100){
                    element5[0].classList.add('error-input');
                    MvUtils.showMessage('error', 'El calle no puede tener más de 100 caracteres');
                    return;
                }
                if(vm.usuario.direcciones[0].nro === undefined) {
                    element6[0].classList.add('error-input');
                    MvUtils.showMessage('error', 'El número no puede ser mayor a 99999');
                    return;
                } else if(vm.usuario.direcciones[0].nro < 0) {
                    element6[0].classList.add('error-input');
                    MvUtils.showMessage('error', 'El número no puede ser negativo');
                    return;
                }
            }

            vm.usuario.rol_id = 2;
            vm.usuario.news_letter = 0;
            if (vm.usuario.usuario_id == undefined) {
                vm.usuario.status = 1;
            } else {
                vm.usuario.status = vm.status ? 1 : 0;
            }
            UserService.save(vm.usuario).then(function (data) {
                //vm.detailsOpen = (data === undefined || data < 0) ? true : false;
                vm.detailsOpen = data.error;

                if(data.error) {
                    element1[0].classList.add('error-input');
                    element2[0].classList.add('error-input');
                    element3[0].classList.add('error-input');
                    element4[0].classList.add('error-input');
                    MvUtils.showMessage('error', data.message);
                }
                else {
                    cleanUsuario();
                    loadUsuarios();
                    element1[0].classList.remove('error-input');
                    element2[0].classList.remove('error-input');
                    element3[0].classList.remove('error-input');
                    element4[0].classList.remove('error-input');
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
                var result = confirm('¿Esta seguro que desea eliminar al proveedor seleccionado?');
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
            vm.usuarios = [];
            vm.detailsOpen = false;
            UserVars.clearCache = true;
            cleanUsuario();
            loadUsuarios();
        }



        function cleanUsuario() {
            vm.usuario = {};
            vm.usuario.comentario = '';
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
