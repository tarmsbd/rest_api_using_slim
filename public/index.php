<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;

require '../vendor/autoload.php';
require '../includes/db_connect.php';
require '../includes/db_operations.php';

$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true
    ]
]);


$app->get('/', function (Request $request, $response, array $args) {
    echo "<h1>Api is working!</h1>";
});

/**
 * endpoint: users
 * job: get all users
 * response: email,password,name,school
 * method: GET,POST
 */

$app->map(['GET', 'POST'], '/users', function (Request $request, $response, array $args) {
    $db = new DbOperations;
    $users = $db->getUsers();

    $response_data = array();

    $response_data['error'] = false;
    $response_data['status'] = 'successfull';
    $response_data['users'] = $users;

    $response->write(json_encode($response_data))
        ->withHeader('Content-type', 'application/json')
        ->withStatus(200);
});

/**
 * endpoint: /user/id
 * job: get user by id
 * parameters: email,password,name,school
 * method: POST
 */

$app->get('/user/{id}', function (Request $request, $response, array $args) {
    $id = $args['id'];

    $db = new DbOperations;
    $user = $db->getUserById($id);
    $response_data = array();

    if ($user['email'] != null) {
        $response_data['error'] = false;
        $response_data['status'] = 'successfull';
        $response_data['users'] = $user;
    } else {
        $response_data['error'] = true;
        $response_data['status'] = 'No user found';
    }

    $response->write(json_encode($response_data))
        ->withHeader('Content-type', 'application/json')
        ->withStatus(200);
});

/**
 * endpoint: /user
 * job: create user
 * parameters: email,password,name,school
 * method: POST
 */

$app->post('/user', function (Request $request, $response) {

    if (!haveEmptyParams(array('email', 'password', 'name', 'school'), $request, $response)) {
        $request_data = $request->getParsedBody();

        $email = $request_data['email'];
        $password = $request_data['password'];
        $name = $request_data['name'];
        $school = $request_data['school'];

        if (!validateEmail($email)) {
            $message = array();
            $message['error'] = true;
            $message['message'] = "Bad email format";

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        }

        if (!validatePassword($password)) {
            $message = array();
            $message['error'] = true;
            $message['message'] = "Bad password format";

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        }

        $hash_password = password_hash($password, PASSWORD_DEFAULT);

        $db = new DbOperations;

        $result = $db->createUser($email, $hash_password, $name, $school);

        if ($result == USER_CREATED) {
            $message = array();
            $message['error'] = false;
            $message['message'] = "User created successfully";

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(201);
        } else if ($result == USER_FAILURE) {
            $message = array();
            $message['error'] = true;
            $message['message'] = "Some error occured";

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        } else if ($result == USER_EXIST) {
            $message = array();
            $message['error'] = true;
            $message['message'] = "User already exist!";

            $response->write(json_encode($message));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(422);
        }

        $message = array();
        $message['error'] = true;
        $message['message'] = "An error occured";

        $response->write(json_encode($message));

        return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(422);
    }
});

/**
 * endpoint: /user/id
 * job: update user
 * parameters: email,name,school
 * method: PUT
 */

$app->put('/user/{id}', function (Request $request, $response, $args) {
    // update user by $args['id']
    if (!haveEmptyParams(array('email', 'name', 'school'), $request, $response)) {
        $db = new DbOperations;

        $user = $request->getParsedBody();
        $result = $db->updateUser($args['id'], $user);

        $response_data = array();

        $response_data['error'] = false;
        $response_data['status'] = 'User Updated!';
        $response_data['user'] = $user;

        $response->write(json_encode($response_data))
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);
    }
});

$app->delete('/user/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $db = new DbOperations;

    $user = $db->getUserById($id);
    if ($user['email'] != null) {
        $db->deleteUser($id);

        $response_data = array();
        $response_data['error'] = false;
        $response_data['status'] = "User $id Deleted!";
    } else {
        $response_data['error'] = true;
        $response_data['status'] = 'No user found';
    }

    $response->write(json_encode($response_data))
        ->withHeader('Content-type', 'application/json')
        ->withStatus(200);
});

function haveEmptyParams($required_params, $request, $response)
{
    // $mediaType = $request->getMediaType();
    // print_r($mediaType);

    $error = false;

    $error_params = '';

    if ($request->getContentType() == "application/json") {
        $request_params = $request->getParsedBody();;
    } else {
        $request_params = $_REQUEST;
    }

    foreach ($required_params as $param) {
        if (!isset($request_params[$param]) || strlen($request_params[$param]) < 0) {
            $error = true;
            $error_params .= $param . ', ';
        }
    }

    if ($error) {
        $error_detail = array();

        $error_detail['error'] = true;
        $error_detail['message'] = 'Required Parameters ' . substr($error_params, 0, -2) . ' are missing';
        // $error_detail['data'] = $required_params;

        $response->write(json_encode($error_detail));
    }

    return $error;
}

function validateEmail($email)
{
    return ($email . matches(EMAIL_PATTERN));
}

function validatePassword($password)
{
    return $password . matches(PASSWORD_PATTERN);
}

function validateMobile($mobile)
{
    return $mobile . matches(MOBILE_PATTERN);
}

$app->run();
