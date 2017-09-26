<?php

namespace App\Http\Controllers;

use Log;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use App\Models\Card;
use App\Models\User;

/**
 * Class CardController.
 * @codeCoverageIgnore
 */
class CardController extends Controller
{
    const CARD_TYPE_HACKER = 1;
    const CARD_TYPE_EXEC = 2;

    const SHEET_WIDTH_PX = 3300;
    const SHEET_HEIGHT_PX = 2550;

    const CARD_WIDTH_PX = 900;
    const CARD_HEIGHT_PX = 1200;

    /**
     * Stitches user access cards together into a PDF.
     * @param array|null $user_ids
     */
    public static function stitchAccessCards($role = User::ROLE_HACKER)
    {
        $paths = Card::whereNotNull('filename')->where('role', $role)->get()->pluck('filename')->toArray();
        $pages = array_chunk($paths, 6);
        $whitePixel = new ImagickPixel('#FFFFFF');
        $roleColors = [
            User::ROLE_HACKER => '#24133A',
            User::ROLE_SPONSOR => '#0CB3C1',
            User::ROLE_ORGANIZER => '#ED1E7E',
        ];
        $roleColor = new ImagickPixel($roleColors[$role]); //todo: based on role

        $pageNum = 0;
        foreach ($pages as $page) {
            $image = new Imagick();
            $image->newImage(self::SHEET_WIDTH_PX, self::SHEET_HEIGHT_PX, $roleColor);
            $image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
            $image->setImageResolution(300, 300);
            $image->setImageFormat('jpg');

            //6xhammers BG
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/hammers6.png'), 'rb'));
            $item1raw->cropThumbnailImage(self::SHEET_WIDTH_PX, self::SHEET_HEIGHT_PX);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 0, 0);

            //add some bleed to the role stripe, underneath where 6up cards are placed
            $roleStripe = new ImagickDraw();
            $roleStripe->setFillColor($whitePixel);
            $roleStripe->rectangle(0, 1085, self::SHEET_WIDTH_PX, 1475);
            $image->drawImage($roleStripe);

            $cardPositions = [
                0=>['x'=>150,   'y'=>80],
                1=>['x'=>1200,  'y'=>80],
                2=>['x'=>2250,  'y'=>80],
                3=>['x'=>150,   'y'=>1279],
                4=>['x'=>1200,  'y'=>1279],
                5=>['x'=>2250,  'y'=>1279],
            ];
            for ($cardLayoutPosNum = 0; $cardLayoutPosNum < 6; $cardLayoutPosNum++) {
                if (isset($page[$cardLayoutPosNum])) {
                    $card = new Imagick();
                    $card->readImageFile(fopen(public_path().'/'.$page[$cardLayoutPosNum], 'rb'));
                    $card->rotateimage($whitePixel, $cardLayoutPosNum >= 3 ? 180 : 0); //rotate bottom 3 by 180
                    $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, $cardPositions[$cardLayoutPosNum]['x'], $cardPositions[$cardLayoutPosNum]['y']);
                    $card->clear();
                    $card->destroy();
                }
            }

            $fileName = "cards-output/${role}-layout-${pageNum}.pdf";
            $path = public_path().'/'.$fileName;
            $image->writeImage($path);
            $image->clear();
            $image->destroy();
            $pageNum++;
        }
    }

    public static function generateTableNumberImage($num)
    {
        $headingFont = resource_path('assets/fonts/MoonBold.ttf');
        $whitePixel = new ImagickPixel('#FFFFFF');
        $bluePixel = new ImagickPixel('#1A4A98');

        $image = new Imagick();
        $image->newImage(self::SHEET_WIDTH_PX, self::SHEET_HEIGHT_PX, $whitePixel);
        $image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
        $image->setImageResolution(300, 300);
        $image->setImageFormat('jpg');

        $roleStripe = new ImagickDraw();
        $roleStripe->setFillColor($bluePixel);
        $roleStripe->rectangle(0, 1275, self::SHEET_WIDTH_PX, 1276);
        $image->drawImage($roleStripe);

        $BMTextLine = new ImagickDraw();
        $BMTextLine->setFont($headingFont);
        $BMTextLine->setFontSize(200);
        $BMTextLine->setFillColor($bluePixel);

        $image->annotateImage($BMTextLine, 2000, 600, 0, $num);

        $image->annotateImage($BMTextLine, 1300, 1950, 180, $num);

        $fileName = "table-numbers/table-${num}.pdf";
        $path = public_path().'/'.$fileName;
        $image->writeImage($path);
    }

    /**
     * @param $skill
     * @param $skill
     * @return Imagick the icon
     */
    public static function getSizedSkillIcon($skill)
    {
        $icon = new Imagick();
        $icon->readImageFile(fopen(resource_path("assets/language_icons/${skill}.png"), 'rb'));
        $icon->cropThumbnailImage(130, 130);

        return $icon;
    }

    /**
     * Generates an access card image.
     * @return string file URI
     */
    public static function generateAccessCardImage(Card $card)
    {

        //globals
        $whitePixel = new ImagickPixel('#FFFFFF');
        $blackPixel = new ImagickPixel('#000000');

        $moonBold = resource_path('assets/fonts/MoonBold.ttf');
        $moonLight = resource_path('assets/fonts/MoonLight.ttf');
        $image = new Imagick();
        $image->newImage(self::CARD_WIDTH_PX, self::CARD_HEIGHT_PX, new ImagickPixel('transparent'));
        $image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
        $image->setImageResolution(300, 300);

        /* SKILLS ICONS */
        $skillRow = $card->skills;
        $skills = $skillRow && $skillRow != 'null' ? explode(',', substr($skillRow, 1, strlen($skillRow) - 2)) : [];
        $skills = array_values(array_filter($skills, function($value) { return $value !== ''; }));
        $skillsYPos = 730;

        if (count($skills) == 3) {
            $image->compositeImage(self::getSizedSkillIcon($skills[0]), IMAGICK::COMPOSITE_DEFAULT, 250, $skillsYPos);
            $image->compositeImage(self::getSizedSkillIcon($skills[1]), IMAGICK::COMPOSITE_DEFAULT, 400, $skillsYPos);
            $image->compositeImage(self::getSizedSkillIcon($skills[2]), IMAGICK::COMPOSITE_DEFAULT, 550, $skillsYPos);
        } elseif (count($skills) == 2) {
            $image->compositeImage(self::getSizedSkillIcon($skills[0]), IMAGICK::COMPOSITE_DEFAULT, 320, $skillsYPos);
            $image->compositeImage(self::getSizedSkillIcon($skills[1]), IMAGICK::COMPOSITE_DEFAULT, 475, $skillsYPos);
        } elseif (count($skills) == 1) {
            $image->compositeImage(self::getSizedSkillIcon($skills[0]), IMAGICK::COMPOSITE_DEFAULT, 400, $skillsYPos);
        }

        /* Add BOILERMAKE text */
        $item1raw = new Imagick();
        $item1raw->readImageFile(fopen(resource_path('assets/boilermaketxt.png'), 'rb'));
        $item1raw->cropThumbnailImage(519, 64);
        $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 187, 84);

        /* Add Name */
        $namePosition = 420;
        $nameTextLine = new ImagickDraw();
        $nameTextLine->setFont($moonBold);
        $nameTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $nameTextLine->setTextKerning(2);
        $nameTextLine->setFontSize(80);
        $nameTextLine->setFillColor($whitePixel);
        $image->annotateImage($nameTextLine, self::CARD_WIDTH_PX / 2, $namePosition, 0, $card->name);

        /* Add School */
        $schoolTextLine = new ImagickDraw();
        $schoolTextLine->setFont($moonLight);
        $schoolTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $schoolTextLine->setTextKerning(2);
        $schoolTextLine->setFontSize(45);
        $schoolTextLine->setFillColor($whitePixel);
        $image->annotateImage($schoolTextLine, self::CARD_WIDTH_PX / 2, $namePosition + 60, 0, $card->subtitle);

        /* Add role */
        //white stripe
        $roleStripe = new ImagickDraw();
        $roleStripe->setFillColor($whitePixel);
        $roleStripe->rectangle(0, 1007, self::CARD_WIDTH_PX, self::CARD_HEIGHT_PX);
        $image->drawImage($roleStripe);
        //add role text
        $roleTextLine = new ImagickDraw();
        $roleTextLine->setFont($moonBold);
        $roleTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $roleTextLine->setTextKerning(2);
        $roleTextLine->setFontSize(70);
        $roleTextLine->setFillColor($blackPixel);
        $roleText = strtoupper($card->role);
        $image->annotateImage($roleTextLine, self::CARD_WIDTH_PX / 2, 1120, 0, $roleText);

        /* SAVE! */
        $fileName = 'cards/card_'.$card->role.'_'.$card->id.'.png';
        $path = public_path().'/'.$fileName;
        $card->filename = $fileName;
        $card->save();
        $image->writeImage($path);
        $image->clear();
        $image->destroy();
        Log::info('Saved card for card #'.$card->id.' to: '.$fileName);
    }
}
