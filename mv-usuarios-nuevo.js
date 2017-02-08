(function () {
    'use strict';

    angular.module('mvUsuariosNuevo', [])
        .component('mvUsuariosNuevo', mvUsuariosNuevo());

    function mvUsuariosNuevo() {
        return {
            bindings: {
                usuario: '=',
                visibility: '='
            },
            templateUrl: window.installPath + '/mv-angular-usuarios/mv-usuarios-nuevo.html',
            controller: MvUsuariosController
        }
    }

    MvUsuariosController.$inject = ['UserService'];
    /**
     * @param AcUsuarios
     * @constructor
     */
    function MvUsuariosController(UserService) {
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
