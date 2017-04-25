(function () {
    'use strict';

    angular.module('mvCancelarDeuda', ['ngRoute'])
        .component('mvCancelarDeuda', MvCancelarDeuda());

    function MvCancelarDeuda() {
        return {
            bindings: {
                usuario: '='
            },
            templateUrl: window.installPath + '/mv-angular-usuarios/mv-cancelar-deuda.html',
            controller: MvCancelarDeudaController
        }
    }


    MvCancelarDeudaController.$inject = ["$scope", "$routeParams", "$location", "MovimientosService", 'UserService', 'MvUtils'];
    function MvCancelarDeudaController($scope, $routeParams, $location, MovimientosService, UserService, MvUtils) {

        var vm = this;

        vm.comentario = '';
        vm.subtipo = '00';
        vm.forma_pago = '01';
        vm.saldo = 0.00;
        vm.usuario_id = UserService.getFromToken().data.id;

        //funciones
        vm.save = save;


        $scope.$watch('$ctrl.usuario', function () {
            console.log(vm.usuario);
            vm.saldo = (vm.usuario == undefined) ? 0.00 : parseFloat(vm.usuario.saldo) * -1;
        });

        function getSaldo(saldo1, saldo2) {
            var saldo = saldo1 + saldo2;
            if(saldo > 0.00)
                saldo = 0.00;
            return saldo;
        }

        function save() {
            //sucursal_id, pagando * total, cliente_id, comentario, usuario_id
            //(tipo_asiento, subtipo_asiento, sucursal_id, forma_pago, transferencia_desde, total, descuento, detalle, items, cliente_id, usuario_id, comentario, callback)
            vm.comentario = "Cancelación de deuda " + vm.comentario;

            MovimientosService.armarMovimiento('015', vm.subtipo, UserService.getFromToken().data.sucursal_id, UserService.getFromToken().data.caja_id, vm.forma_pago, '', vm.saldo, '', vm.comentario, '', vm.usuario_id, 1, vm.comentario)
                .then(function (data) {
                    console.log(data);
                    if(data.status == 200) {
                        var saldo = getSaldo(parseFloat(vm.usuario.saldo), parseFloat(vm.saldo));
                        UserService.actualizarSaldo(vm.usuario.usuario_id, saldo).then(function(data){
                            if(data){
                                vm.comentario = '';
                                vm.subtipo = '00';
                                vm.forma_pago = '01';
                                vm.saldo = 0.00;
                                $location.path('/reportes/deudores');
                            }
                        }).catch(function(error){
                            console.log(error);
                        });
                    } else {
                        MvUtils.showMessage('error', 'Error generando el movimiento');
                    }
                }).catch(function(error){
                    console.log(error);
                });
        }


    }


})();

