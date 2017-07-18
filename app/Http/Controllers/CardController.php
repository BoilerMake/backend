<?php

namespace App\Http\Controllers;

use Log;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use App\Models\User;
use App\Models\PuzzleProgress;

/**
 * Class CardController.
 * @codeCoverageIgnore
 */
class CardController extends Controller
{
    const CARD_TYPE_HACKER = 1;
    const CARD_TYPE_EXEC = 2;

    /**
     * Stitches user access cards together into a PDF.
     * @param array|null $user_ids
     */
    public static function stitchAccessCards($user_ids = null)
    {
        if ($user_ids) {
            $users = User::whereIn('id', $user_ids)->get()->pluck('card_image')->toArray();
        } else {
            $users = User::whereNotNull('card_image')->get()->pluck('card_image')->toArray();
        }
        $pages = array_chunk($users, 6);

        $whitePixel = new ImagickPixel('#FFFFFF');

//        $combined   =   new Imagick();

        $x = 0;
        foreach ($pages as $page) {
            $image = new Imagick();
            $image->newImage(3300, 2550, $whitePixel);
            $image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
            $image->setImageResolution(300, 300);
            $image->setImageFormat('jpg');

            if (isset($page[0])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[0], 'rb'));
                $card->rotateimage($whitePixel, 180);
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 150, 80);
                $card->clear();
                $card->destroy();
            }

            if (isset($page[1])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[1], 'rb'));
                $card->rotateimage($whitePixel, 180);
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 1200, 80);
                $card->clear();
                $card->destroy();
            }

            if (isset($page[2])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[2], 'rb'));
                $card->rotateimage($whitePixel, 180);
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 2250, 80);
                $card->clear();
                $card->destroy();
            }

            if (isset($page[3])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[3], 'rb'));
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 150, 1279);
                $card->clear();
                $card->destroy();
            }

            if (isset($page[4])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[4], 'rb'));
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 1200, 1279);
                $card->clear();
                $card->destroy();
            }

            if (isset($page[5])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[5], 'rb'));
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 2250, 1279);
                $card->clear();
                $card->destroy();
            }

//            $combined->addImage( $image );
//            $combined->setImageFormat("jpg");
            $fileName = 'cards-output/layout-'.$x.'.pdf';
            $path = public_path().'/'.$fileName;
            $image->writeImage($path);
            $x++;
            $image->clear();
            $image->destroy();
        }
    }

    /**
     * Generates an access card image for a given user.
     * @param user $user_id the user's id
     * @return string file URI
     */
    public static function generateAccessCardImage($user_id)
    {
        $user = User::with('application', 'application.school')->find($user_id);
        $schoolName = null;
        if ($user->hasRole('exec')) {
            $cardType = self::CARD_TYPE_EXEC;
            $schoolName = 'Purdue University';
        } elseif ($user->hasRole('hacker')) {
            $cardType = self::CARD_TYPE_HACKER;
            $schoolName = $user->application->school
                ? $user->application->school->name
                : '';
            $schoolName = $user->application->school && $user->application->school->display_name
                ? $user->application->school->display_name
                : $schoolName;
        } else {
            return 'error';
        }

        $isExecCard = $cardType == self::CARD_TYPE_EXEC;

        //globals
        $whitePixel = new ImagickPixel('#FFFFFF');
        $bluePixel = new ImagickPixel('#1A4A98');
        $allergen1 = new ImagickPixel('#59436A');
        $allergenYellow = new ImagickPixel('#F6CF56');
        $greenPixel = new ImagickPixel('#45955E');
        $blackPixel = new ImagickPixel('#000000');
        $mainFont = resource_path('assets/fonts/Exo2-Regular.ttf');
        $headingFont = resource_path('assets/fonts/SanFranciscoDisplay-Regular.otf');

        /* New image */
        $fullWidth = 900; //3in @ 300ppi
        $image = new Imagick();
        $image->newImage($fullWidth, 1200, $whitePixel);
        $image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
        $image->setImageResolution(300, 300);

        /* GENERATE SKILLS ICONS */
        $skills = json_decode($user->application->skills, true);

        $puzzleUsers = PuzzleProgress::where('puzzle_id', 5)->get()->pluck('user_id')->toArray();
        if (in_array($user->id, $puzzleUsers)) {
            $skills[] = 'puzzle';
        }
        $skillsYPos = 640;
        if ($isExecCard) {
            $skillsYPos += 100;
        }

        if (count($skills) == 4) {
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[0].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 165, $skillsYPos);

            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[1].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 320, $skillsYPos);

            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[2].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 475, $skillsYPos);

            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[3].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 630, $skillsYPos);
        }

        if (count($skills) == 3) {
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[0].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 250, $skillsYPos);

            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[1].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 400, $skillsYPos);

            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[2].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 550, $skillsYPos);
        }
        if (count($skills) == 2) {
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[0].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 320, $skillsYPos);

            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[1].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 475, $skillsYPos);
        }
        if (count($skills) == 1) {
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[0].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 400, $skillsYPos);
        }

        if (! $isExecCard) {
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/logo_s17.png'), 'rb'));
            $item1raw->cropThumbnailImage(210, 210);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 92, 50);

            $BMTextLine = new ImagickDraw();
            $BMTextLine->setFont($headingFont);
            $BMTextLine->setFontSize(80);
            $BMTextLine->setFillColor($bluePixel);

            $image->annotateImage($BMTextLine, 313, 180, 0, 'BOILERMAKE');
        } else {
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/logo_s17@3x.png'), 'rb'));
            $item1raw->cropThumbnailImage(600, 600);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 150, 0);
        }

        $namePosition = 440;
        if ($isExecCard) {
            $namePosition += 180;
        }

        $nameTextLine = new ImagickDraw();
        $nameTextLine->setFont($mainFont);
        $nameTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $nameTextLine->setTextKerning(2);
        $nameTextLine->setFontSize($isExecCard ? 80 : 62);
        $nameTextLine->setFillColor($blackPixel);

        $image->annotateImage($nameTextLine, $fullWidth / 2, $namePosition, 0, $user->first_name.' '.$user->last_name);

        $schoolTextLine = new ImagickDraw();
        $schoolTextLine->setFont($mainFont);
        $schoolTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $schoolTextLine->setTextKerning(2);
        $schoolTextLine->setFontSize(45);
        $schoolTextLine->setFillColor($blackPixel);

        $image->annotateImage($schoolTextLine, $fullWidth / 2, $namePosition + 55, 0, $schoolName);

        //add the stripe
        $roleStripe = new ImagickDraw();
        $roleStripe->setFillColor($isExecCard ? $bluePixel : $greenPixel);
        $roleStripe->rectangle(0, 975, $fullWidth, 1200);
        $image->drawImage($roleStripe);

        //vegetarian/vegan strip
        if ($user->application->diet == 1 || $user->application->diet == 3) {
            $dietStripe = new ImagickDraw();
            $dietStripe->setFillColor($allergenYellow);
            $dietStripe->rectangle($fullWidth - 72, 975, $fullWidth, 1200);
            $image->drawImage($dietStripe);
        }

        //allergen strip
        if ($user->application->diet == 3) {
            $dietStripe = new ImagickDraw();
            $dietStripe->setFillColor($allergen1);
            $dietStripe->rectangle($fullWidth - 72, 975, $fullWidth - 32, 1200);
            $image->drawImage($dietStripe);
        }

        //alergent strip wihtout veg+vegan
        if ($user->application->diet == 2) {
            $dietStripe = new ImagickDraw();
            $dietStripe->setFillColor($allergen1);
            $dietStripe->rectangle($fullWidth - 72, 975, $fullWidth, 1200);
            $image->drawImage($dietStripe);
        }

        $roleTextLine = new ImagickDraw();
        $roleTextLine->setFont($mainFont);
        $roleTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $roleTextLine->setTextKerning(2);
        $roleTextLine->setFontSize($isExecCard ? 82 : 68);
        $roleTextLine->setFillColor($whitePixel);
        $roleText = $isExecCard ? 'ORGANIZER' : 'HACKER';
        $image->annotateImage($roleTextLine, $fullWidth / 2, 1115, 0, $roleText);

        $fileName = 'cards/card_'.$cardType.'_'.$user_id.'.jpg';
        $path = public_path().'/'.$fileName;
        $user->card_image = $fileName;
        $user->save();
        $image->writeImage($path);
        $image->clear();
        $image->destroy();
        Log::info('Saved card for user #'.$user_id.' to: '.$fileName);
    }
}
