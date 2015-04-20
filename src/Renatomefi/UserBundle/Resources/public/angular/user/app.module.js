'use strict';

var sammuiUser = angular.module('sammui.user', [
    'sammui.userInfoControllers'
]);

sammuiUser.config(function ($locationProvider, $routeProvider) {
    $routeProvider.when('/user/info', {
        templateUrl: '/bundles/user/angular/views/info/info.html',
        templatePreload: true,
        controller:'UserInfo',
        reloadOnSearch: false
    });
    
});