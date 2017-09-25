<?php

namespace App\Http\Controllers;

use Log;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use App\Models\User;

/**
 * Class CardController.
 * @codeCoverageIgnore
 */
class CardController extends Controller
{
    const CARD_TYPE_HACKER = 1;
    const CARD_TYPE_EXEC = 2;
    const CARD_ROLE_STRIPE_EXEC = '#1A4A98';

    /**
     * Stitches user access cards together into a PDF.
     * @param array|null $user_ids
     */
    public static function stitchAccessCards($user_ids = null, $stripeColor = self::CARD_ROLE_STRIPE_EXEC)
    {
        if ($user_ids) {
            $paths = User::whereIn('id', $user_ids)->get()->pluck('card_image')->toArray();
        } else {
            $paths = User::whereNotNull('card_image')->get()->pluck('card_image')->toArray();
        }
        $pages = array_chunk($paths, 6);
        $whitePixel = new ImagickPixel('#FFFFFF');
        $color1 = new ImagickPixel('#24133A');

        $x = 0;
        foreach ($pages as $page) {
            $image = new Imagick();
            $image->newImage(3300, 2550, $color1);
            $image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
            $image->setImageResolution(300, 300);
            $image->setImageFormat('jpg');

            //hammers BG
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/hammers6.png'), 'rb'));
            $item1raw->cropThumbnailImage(3300, 2550);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 0, 0);

            //add role stripe w/ bleed
            $roleStripe = new ImagickDraw();
            $roleStripe->setFillColor($whitePixel);
            $roleStripe->rectangle(0, 1085, 3300, 1475);
            $image->drawImage($roleStripe);

            if (isset($page[0])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[0], 'rb'));
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 150, 80);
                $card->clear();
                $card->destroy();
            }

            if (isset($page[1])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[1], 'rb'));
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 1200, 80);
                $card->clear();
                $card->destroy();
            }

            if (isset($page[2])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[2], 'rb'));
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 2250, 80);
                $card->clear();
                $card->destroy();
            }

            if (isset($page[3])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[3], 'rb'));
                $card->rotateimage($whitePixel, 180);
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 150, 1279);
                $card->clear();
                $card->destroy();
            }

            if (isset($page[4])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[4], 'rb'));
                $card->rotateimage($whitePixel, 180);
                $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, 1200, 1279);
                $card->clear();
                $card->destroy();
            }

            if (isset($page[5])) {
                $card = new Imagick();
                $card->readImageFile(fopen(public_path().'/'.$page[5], 'rb'));
                $card->rotateimage($whitePixel, 180);
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

    public static function generateTableNumberImage($num) {
        $headingFont = resource_path('assets/fonts/MoonBold.ttf');
        $whitePixel = new ImagickPixel('#FFFFFF');
        $bluePixel = new ImagickPixel('#1A4A98');

        $image = new Imagick();
        $image->newImage(3300, 2550, $whitePixel);
        $image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
        $image->setImageResolution(300, 300);
        $image->setImageFormat('jpg');


        $roleStripe = new ImagickDraw();
        $roleStripe->setFillColor($bluePixel);
        $roleStripe->rectangle(0, 1275, 3300, 1276);
        $image->drawImage($roleStripe);


        $BMTextLine = new ImagickDraw();
        $BMTextLine->setFont($headingFont);
        $BMTextLine->setFontSize(200);
        $BMTextLine->setFillColor($bluePixel);

        $image->annotateImage($BMTextLine, 2000, 600, 0, $num);

        $image->annotateImage($BMTextLine, 1300 ,1950, 180, $num);



        $fileName = "table-numbers/table-${num}.pdf";
        $path = public_path().'/'.$fileName;
        $image->writeImage($path);
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

        //globals
        $whitePixel = new ImagickPixel('#FFFFFF');
        $blackPixel = new ImagickPixel('#000000');

        $moonBold = resource_path('assets/fonts/MoonBold.ttf');
        $moonLight = resource_path('assets/fonts/MoonLight.ttf');
        $transparentPixel = new ImagickPixel('transparent');
        /* New image */
        $fullWidth = 900; //3in @ 300ppi
        $image = new Imagick();
        $image->newImage($fullWidth, 1200, $transparentPixel);
//        $image->setImageAlpha(0);
        $image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
        $image->setImageResolution(300, 300);

        /* GENERATE SKILLS ICONS */
        $skillRow = $user->application->skills;
        $skills = $skillRow && $skillRow != "null" ? explode(",",substr($skillRow,1,strlen($skillRow)-2)) : [];
//            explode(",",json_decode($user->application->skills, true));

        $skillsYPos = 830;

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

        $item1raw = new Imagick();
        $item1raw->readImageFile(fopen(resource_path('assets/boilermaketxt.png'), 'rb'));
        $item1raw->cropThumbnailImage(519, 64);
        $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 187, 84);

        $namePosition = 420;

        $nameTextLine = new ImagickDraw();
        $nameTextLine->setFont($moonBold);
        $nameTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $nameTextLine->setTextKerning(2);
        $nameTextLine->setFontSize(80);
        $nameTextLine->setFillColor($whitePixel);

        $image->annotateImage($nameTextLine, $fullWidth / 2, $namePosition, 0, $user->first_name.' '.$user->last_name);

        $schoolTextLine = new ImagickDraw();
        $schoolTextLine->setFont($moonLight);
        $schoolTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $schoolTextLine->setTextKerning(2);
        $schoolTextLine->setFontSize(45);
        $schoolTextLine->setFillColor($whitePixel);

        $image->annotateImage($schoolTextLine, $fullWidth / 2, $namePosition + 60, 0, $schoolName);

        //add the stripe
        $roleStripe = new ImagickDraw();
        $roleStripe->setFillColor($whitePixel);
        $roleStripe->rectangle(0, 1007, $fullWidth, 1200);
        $image->drawImage($roleStripe);

        $roleTextLine = new ImagickDraw();
        $roleTextLine->setFont($moonBold);
        $roleTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $roleTextLine->setTextKerning(2);
        $roleTextLine->setFontSize(70);
        $roleTextLine->setFillColor($blackPixel);
        $roleText = 'HACKER';
        $image->annotateImage($roleTextLine, $fullWidth / 2, 1120, 0, $roleText);

        $fileName = 'cards/card_'.$cardType.'_'.$user_id.'.png';
        $path = public_path().'/'.$fileName;
        $user->card_image = $fileName;
        $user->save();
        $image->writeImage($path);
        $image->clear();
        $image->destroy();
        Log::info('Saved card for user #'.$user_id.' to: '.$fileName);
    }
}
