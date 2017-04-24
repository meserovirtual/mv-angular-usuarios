(function () {

    'use strict';
    angular.module('mvCancelarDeuda', ['ngRoute'])

        .component('mvCancelarDeuda', MvCancelarDeuda());

    function MvCancelarDeuda() {
        return {
            bindings: {
                pedido: '&'
            },
            templateUrl: window.installPath + '/mv-angular-usuarios/mv-cancelar-deuda.html',
            controller: MvCancelarDeudaController
        }
    }


    MvCancelarDeudaController.$inject = ["$routeParams", "$location", "MovimientosService", 'UserService'];
    function MvCancelarDeudaController($routeParams, $location, MovimientosService, UserService) {
        var vm = this;
        vm.comentario = '';
        vm.subtipo = '00';
        vm.forma_pago = '01';
        vm.save = save;
        vm.id = 1;
        vm.pedido = CancelaDeudaPedidoService.pedido;
        vm.pedido.total = parseFloat(vm.pedido.total);
        vm.cliente = {};


        UserService.getDeudorById(vm.id, function (data) {
            data.saldo = parseFloat(data.saldo )*-1;
            vm.cliente = data;

        });

        function save() {
            //sucursal_id, pagando * total, cliente_id, comentario, usuario_id
            //(tipo_asiento, subtipo_asiento, sucursal_id, forma_pago, transferencia_desde, total, descuento, detalle, items, cliente_id, usuario_id, comentario, callback)
            vm.comentario = "Cancelación de deuda " + vm.comentario;

            MovimientosService.armarMovimiento('015', vm.subtipo, UserService.getFromToken().data.sucursal_id, UserService.getFromToken().data.caja_id, vm.forma_pago, '', vm.cliente.saldo, '', vm.comentario, '', vm.id, 1, vm.comentario, function (data) {

                UserService.actualizarSaldo(vm.cliente.cliente_id, parseFloat(vm.cliente.saldo), function(data){
                    if(data){
                        vm.comentario = '';
                        vm.subtipo = '00';
                        vm.forma_pago = '01';
                        $location.path('/listado_deudores');
                        toastr.success('Saldo Actualizado');
                    }

                });



            });


        }


    }


})();

