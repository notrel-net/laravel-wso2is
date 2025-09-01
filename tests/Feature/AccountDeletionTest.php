<?php

use Notrel\LaravelWso2is\Http\Requests\Wso2isAccountDeletionRequest;

test('it creates account deletion request', function () {
    $request = new Wso2isAccountDeletionRequest();

    expect($request)->toBeInstanceOf(Wso2isAccountDeletionRequest::class);
});

test('it has deleteAccount method', function () {
    $request = new Wso2isAccountDeletionRequest();

    expect(method_exists($request, 'deleteAccount'))->toBeTrue();
});

test('it has protected redirect method', function () {
    $request = new Wso2isAccountDeletionRequest();

    expect(method_exists($request, 'redirect'))->toBeTrue();
});

test('it has protected deleteUsing method', function () {
    $request = new Wso2isAccountDeletionRequest();

    expect(method_exists($request, 'deleteUsing'))->toBeTrue();
});

test('it has protected deleteUserFromWso2is method', function () {
    $request = new Wso2isAccountDeletionRequest();

    expect(method_exists($request, 'deleteUserFromWso2is'))->toBeTrue();
});
