(function () {
    'use strict';

    angular.module('acUsuariosNuevo', [])
        .component('acUsuariosNuevo', acUsuariosNuevo());

    function acUsuariosNuevo() {
        return {
            bindings: {
                usuario: '=',
                visibility: '='
            },
            templateUrl: window.installPath + '/ac-angular-usuarios/ac-usuarios-nuevo.html',
            controller: AcUsuariosController
        }
    }

    AcUsuariosController.$inject = ['UserService'];
    /**
     * @param AcUsuarios
     * @constructor
     */
    function AcUsuariosController(UserService) {
        var vm = this;

        vm.usuarios = [];
        vm.usuario.tipo_doc = '0';
        vm.usuario.rol_id = '3';

        vm.save = save;


        function save() {

            UserService.save(vm.usuario).then(function (data) {
                return UserService.get();
            }).then(function (data) {
                vm.visibility = false;
                //setData(data);
            });

        }


    }


})();
