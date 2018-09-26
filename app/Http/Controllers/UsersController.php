<?php

namespace App\Http\Controllers;

use Auth;
use Request;
use App\Models\User;
use App\Models\Application;

/*
 * Handles basic user functions, such as updating and getting the user + their application
 */
class UsersController extends Controller
{
    /**
     * GET /users/me
     * Gets the currently logged in User.
     * @return User|null
     */
    public function getMe()
    {
        return response()->success(Auth::user());
    }

    /**
     * Updates the current user, based on JSON payload
     * PUT /users/me.
     * @return mixed
     */
    public function updateMe()
    {
        $user = Auth::user();
        $data = json_decode(Request::getContent(), true);

        foreach ($data as $key => $value) {
            //update the user info
            if (in_array($key, [
                User::FIELD_EMAIL,
                User::FIELD_FIRSTNAME,
                User::FIELD_LASTNAME,
                User::FIELD_PHONE,
                User::FIELD_PROJECT_IDEA,
                User::FIELD_TEAM_NAMES,
            ])) {
                $user->$key = $value;
                $user->save();
            }
        }

        return response()->success('ok');
    }

    /**
     * Gets an application, including validation information.
     * GET /users/me/application.
     * @return mixed
     */
    public function getApplication()
    {
        $user = Auth::user();
        $application = $user->getApplication();
        $application['skills'] = json_decode($application->skills, true); //todo: put this as a model prop

        foreach (Application::USER_FIELDS_TO_INJECT as $x) {
            $application[$x] = $user->$x;
        }
        $application['resume_get_url'] = $application[Application::FIELD_RESUME_UPLOADED_FLAG] ? $application->user->resumeURL() : null;
        $application['resume_put_url'] = $application->user->resumeURL('put');

        if (! Application::isPhaseInEffect(Application::PHASE_DECISIONS_REVEALED)) {
            //don't reveal decisions early
            $application->setHidden(['decision', 'emailed_decision']);
        }
        $application['is_rsvp_confirmed'] = $application['rsvp'];

        return response()->success([
            'applicationForm' => $application,
            'validation'  => $application->validationDetails(),
            'phase'       => Application::getCurrentPhase(),
            'message'     => $application->completed ? 'Your application is completed!' : 'Your application is not yet complete though!',
        ]);
    }

    /**
     * Updates an application based on JSON payload in request body
     * PUT /users/me/application.
     * @return mixed
     */
    public function updateApplication()
    {
        $user = Auth::user();
        $data = json_decode(Request::getContent(), true);
        $application = $user->getApplication();

        foreach ($data as $key => $value) {
            if (in_array($key, Application::INITIAL_FIELDS)) {
                $application->$key = $value;
            } elseif (in_array($key, Application::USER_FIELDS_TO_INJECT)) {
                //we combine some User fields onto Application, since name lives on User and it makes things easier.
                $user->$key = $value;
            } elseif ($key == Application::FIELD_GITHUB) {
                if (! $user->github_user_id) {
                    //let them change username IF they are not linked to a Github account
                    $application->github = User::extractUsernameFromURL($value);
                }
            } elseif ($key == Application::FIELD_LINKEDIN) {
                $application[Application::FIELD_LINKEDIN] = User::extractUsernameFromURL($value);
            } elseif ($key == Application::FIELD_RSVP_FLAG) {
                //TODO: check RSVP phase
                //check to make sure they were actually accepted in case we have some sneaky mofos
                if ($application->decision == Application::DECISION_ACCEPT) {
                    $application->rsvp = $value;
                }
            } elseif ($key == 'skills') {
                //                TODO: check RSVP phase
                $application->skills = json_encode($value);
            } elseif (in_array($key, [Application::FIELD_DIET, Application::FIELD_DIET_RESTRICTIONS, Application::FIELD_TSHIRT])) {
                //TODO: check RSVP phase
                $application->$key = $value;
            }
        }
        $user->save();
        $application->save();

        return $this->getApplication();
    }
}
