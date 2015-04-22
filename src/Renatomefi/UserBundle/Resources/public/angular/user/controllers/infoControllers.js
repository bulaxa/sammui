'use strict';

angular.module('sammui.userInfoControllers', ['ngRoute'])
.controller('UserInfo', ['$rootScope', '$scope','$http',
        function ($rootScope,$scope,$http) {
            $http.get('/api/user/manage/users/info').
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


