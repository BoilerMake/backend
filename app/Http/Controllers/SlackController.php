<?php

namespace App\Http\Controllers;

use Log;
use Request;
use Carbon\Carbon;
use App\Models\InterestSignup;

class SlackController extends Controller
{
    protected $textLines = [];
    protected $attachments = [];
    protected $title = '';

    /**
     * This is what /slackapp route hits.
     * @return array
     */
    public function index()
    {
        if (Request::get('token') != env('SLACK_APP_TOKEN')) {
            return response()->error('unauthorized');
        }
        $command = Request::get('command');
        $params = explode(' ', Request::get('text'));
        Log::info("SlackCommand={$command}", ['params'=>$params]);
        if ($command == '/data') {
            $this->processDataCommand($params);
        } else {
            $this->addAttachment('Command not supported.', 'danger');
        }

        return $this->getResponse();
    }

    /**
     * Processes: /data.
     * @param $params
     */
    private function processDataCommand($params)
    {
        if ($params[0] == 'interest') {
            $this->setResponseTitle('Interest Signups');
            $this->addAttachment([
                'Total: '.InterestSignup::count(),
                "\nIn last week: ".InterestSignup::where('created_at', '>', Carbon::now()->addWeeks(-1))->count(),
            ]);
        } elseif ($params[0] == 'help') {
            $this->setResponseTitle('Data tools help');
            $this->addAttachment('interest: view interest signup data');
        } else {
            $this->addAttachment('please give an param from the following: [interest,help]', 'danger');
        }
    }

    /**
     * Set the Title of the responses.
     * @param $title
     */
    private function setResponseTitle($title)
    {
        $this->title = $title;
    }

    /**
     * https://api.slack.com/docs/message-attachments.
     * @param $body
     * @param string $color
     */
    private function addAttachment($body, $color = 'good')
    {
        if (is_array($body)) {
            $text = implode("\n", $body);
        } else {
            $text = $body;
        }
        $this->attachments[] = [
            'text' => $text,
            'color' => $color,
        ];
    }

    /**
     * Build the Slack response JSON.
     * @return array
     */
    private function getResponse()
    {
        if ($this->title) {
            array_unshift($this->textLines, "*{$this->title}*");
        }

        return [
            'text' => implode("\n", $this->textLines),
            'attachments' => $this->attachments,
        ];
    }
}
