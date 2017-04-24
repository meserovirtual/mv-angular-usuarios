(function () {

    'use strict';


    angular.module('mvDeudores', [])
        .component('mvDeudores', mvDeudores());

    function mvDeudores() {
        return {
            bindings: {},
            templateUrl: window.installPath + '/mv-angular-usuarios/mv-deudores.html',
            controller: mvDeudoresController
        }
    }

    mvDeudoresController.$inject = ["$rootScope", "$location", 'UserService', 'UserVars', 'MvUtils'];
    function mvDeudoresController($rootScope, $location, UserService, UserVars, MvUtils) {
        var vm = this;
        vm.usuario = {};
        vm.usuarios = []

        vm.cancel = cancel;


        UserService.getDeudores().then(function (data) {
            vm.usuarios = data.data;
            vm.usuario = data.data[0];
        }).catch(function(error){
            console.log(error);
        });


        function cancel() {
            //vm.usuarios = [];
            vm.usaurio = {};
            vm.detailsOpen = false;
            UserVars.clearCache = true;
        }


        // Implementaci�n de la paginaci�n
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

