'use strict';

angular.module('sammui.userInfoControllers', ['ngRoute'])
.controller('UserInfo', ['$rootScope', '$scope','$http',
        function ($rootScope,$scope,$http) {
            //por get
            $http.get('http://echo.jsontest.com/nome/rafael/key/999').
                success(function(data, status) {
                    $scope.status = status;
                    $scope.data = data;
                }).
                error(function(data, status) {
                    $scope.data = data || "Request failed";
                    $scope.status = status;
              });
            
        }
    ]);


