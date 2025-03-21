<?php

namespace sigawa\mvccore\controllers;

use Mountkenymilk\Fems\models\Buyer;
use sigawa\mvccore\Application;
use sigawa\mvccore\auth\providers\GoogleOAuth;
use sigawa\mvccore\AuthProvider;
use sigawa\mvccore\Controller;
use sigawa\mvccore\Request;
use sigawa\mvccore\Response;

class OAuthController extends Controller
{
    public function googleLogin()
    {
        $google = new GoogleOAuth();
        header("Location: " . $google->getAuthUrl());
        exit;
    }

    public function googleCallback(Request $request, Response $response)
    {
        if (!isset($_GET['code'])) {
            die("Authorization failed.");
        }

        $google = new GoogleOAuth();
        $tokenData = $google->getAccessToken($_GET['code']);
        if (!isset($tokenData['access_token'])) {
            die("Failed to get access token.");
        }

        $userData = $google->getUserData($tokenData['access_token']);

        // Check if user exists, otherwise create them
       
        $user = Buyer::findOne(['email' => $userData['email']]);
        // For this project,. the role_id is assumed as 4 (buyer_id)
        //TODO:  Ensure role_id is assigned correctly
        if (!$user) {
            $user = new Buyer();
            $user->email = $userData['email'];
            $user->role_id = $_ENV['STATIC_ROLE_ID'];
            $user->fname = $userData['family_name'];
            $user->lname = $userData['given_name'];
            $user->save();
        }

        $user = Buyer::findOne(['email' => $userData['email']]);
        // Log in user
        Application::$app->guestLogin($user);
        AuthProvider::setUser($user, true);
        // Check if profile is incomplete
        if (empty($user->phone) || empty($user->fname) || empty($user->lname)) {
            Application::$app->session->set('required_profile', true);
        } else {
            Application::$app->session->remove('required_profile');
        }
        return $response->redirect($_ENV['GOOGLE_CALLBACK_REDIRECT_VIEW']);
    }
}
