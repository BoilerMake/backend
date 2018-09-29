<?php

namespace App\Http\Controllers;

use Log;
use Storage;

class SponsorsController extends Controller
{
    /**
     * Redirects user from secret packet link to the S3 url.
     * @param $secret
     * @return \Illuminate\Http\RedirectResponse|string
     * @codeCoverageIgnore
     */
    public function packet($secret)
    {
        if ($secret != env('PACKET_SECRET')) {
            Log::notice('Unauthorized access to Sponsorship Packet with secret '.$secret);

            return 'not authorized';
        }

        Log::info('Sponsorship packet read');
        $file = Storage::cloud()->get(getenv('S3_PREFIX').'/packet.pdf');

        return response($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="BoilerMake_sponsorship_packet.pdf"',
        ]);
    }
}
