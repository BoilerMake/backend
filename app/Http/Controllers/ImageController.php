<?php

namespace App\Http\Controllers;

use Log;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use App\Models\Card;
use App\Models\User;

/**
 * Class ImageController.
 * This is used to generate access cards and table numbers.
 * How the access cards work:
 * They are generated as 3x4 images with a transparent background.
 *      They include name, subtitle, skill icon, and a white stripe on the bottom with $role
 * They are then stacked 6-up on an 8.5x11 sized image (stitchAccessCards) based in the following order, and saved to a PDF:
 * 1. colored background based on $role (stitchAccessCards is batched per-role).
 *    We don't do background colors in card generation so that we get full bleed for printing
 * 2. 6up transparent image of the hammers
 * 3. White stripe to give bleed for the white background that the role label part has
 * 4. The transparent access cards.
 *
 * @codeCoverageIgnore
 */
class ImageController extends Controller
{
    const SHEET_WIDTH_PX = 3300; //11 in
    const SHEET_HEIGHT_PX = 2550; //8.5 in

    const CARD_WIDTH_PX = 900;  // 3 in
    const CARD_HEIGHT_PX = 1200; // 4 in

    /**
     * Stitches user access cards for a given role together into multiple 6-up PDFs.
     * @param string|null $role
     */
    public static function stitchAccessCards($role = User::ROLE_HACKER)
    {
        $paths = Card::whereNotNull('filename')->where('role', $role)->get()->pluck('filename')->toArray();
        $whitePixel = new ImagickPixel('#FFFFFF');
        //background color depends on role
        $roleColors = [
            User::ROLE_HACKER    => '#24133A',
            User::ROLE_SPONSOR   => '#0CB3C1',
            User::ROLE_ORGANIZER => '#ED1E7E',
            User::ROLE_GUEST     => '#F8AF18',
        ];
        //coordinates for the 6up layout
        $cardPositions = [
            0=>['x'=>150,   'y'=>80],
            1=>['x'=>1200,  'y'=>80],
            2=>['x'=>2250,  'y'=>80],
            3=>['x'=>150,   'y'=>1279],
            4=>['x'=>1200,  'y'=>1279],
            5=>['x'=>2250,  'y'=>1279],
        ];
        $roleColor = new ImagickPixel($roleColors[$role]);
        $pageNum = 0;
        foreach (array_chunk($paths, 6) as $page) {
            //make a 8.5x11 image @ 300dpi
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

            //add some bleed white to the role stripe, underneath where 6up cards are placed
            $roleStripe = new ImagickDraw();
            $roleStripe->setFillColor($whitePixel);
            $roleStripe->rectangle(0, 1085, self::SHEET_WIDTH_PX, 1475);
            $image->drawImage($roleStripe);

            for ($cardLayoutPosNum = 0; $cardLayoutPosNum < 6; $cardLayoutPosNum++) {
                //there could be fewer than 6 if it's the last page, so we need to check
                if (isset($page[$cardLayoutPosNum])) {
                    $card = new Imagick();
                    $card->readImageFile(fopen(public_path().'/'.$page[$cardLayoutPosNum], 'rb'));
                    $card->rotateimage($whitePixel, $cardLayoutPosNum >= 3 ? 180 : 0); //rotate bottom 3 by 180
                    $image->compositeImage($card, IMAGICK::COMPOSITE_DEFAULT, $cardPositions[$cardLayoutPosNum]['x'], $cardPositions[$cardLayoutPosNum]['y']);
                    $card->clear();
                    $card->destroy();
                }
            }
            //save it to a pdf
            $fileName = "cards-output/${role}-layout-${pageNum}.pdf";
            $path = public_path().'/'.$fileName;
            $image->writeImage($path);
            $image->clear();
            $image->destroy();
            $pageNum++;
        }
    }

    /**
     * Generate an a skill icon based on name.
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
     * Brute force find the largest font size that will fit in given width.
     * @param $font - which face
     * @param $text - text to fit
     * @param $maxWidth - bounding box
     * @param $maxFontSize - how big do we go?
     * @return int font size
     */
    public static function getMaxFontSize($font, $text, $maxWidth, $maxFontSize)
    {
        $goodSize = 8;
        for ($x = $goodSize; $x < $maxFontSize; $x += 0.5) {
            $bbox = imageftbbox($goodSize, 0, $font, $text);
            if ($bbox[2] - $bbox[0] > $maxWidth) {
                //we went to far, return last good
                return $goodSize;
            } else {
                $goodSize = $x;
            }
        }

        return $goodSize;
    }

    /**
     * Generates an access card image.
     * @return string file URI
     */
    public static function generateAccessCardImage(Card $card)
    {
        $whitePixel = new ImagickPixel('#FFFFFF');
        $blackPixel = new ImagickPixel('#000000');
        $moonBold = resource_path('assets/fonts/MoonBold.ttf');
        $moonLight = resource_path('assets/fonts/MoonLight.ttf');

        //make a 3x4 image @ 300dpi, with transparent background so we can overlay it on top of hammers + bg
        $image = new Imagick();
        $image->newImage(self::CARD_WIDTH_PX, self::CARD_HEIGHT_PX, new ImagickPixel('transparent'));
        $image->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
        $image->setImageResolution(300, 300);

        /*
         * SKILLS ICONS
         * skills are stored as: 'skill1,skill2' in the DB, so we need to split into array, and remove duplicates
         * */

        $skillRow = $card->skills;
        //turn into array:
        $skills = $skillRow && $skillRow != 'null' ? explode(',', substr($skillRow, 1, strlen($skillRow) - 2)) : [];
        //remove duplicates:
        $skills = array_values(array_filter($skills, function ($value) {
            return $value !== '';
        }));
        $skillsYPos = 730; //position for the icon row
        //someone can have 0...3 skills
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
        $name = $card->name;
        $namePosition = 420;
        $nameTextLine = new ImagickDraw();
        $nameTextLine->setFont($moonBold);
        $nameTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $nameTextLine->setTextKerning(2);
        $nameTextLine->setFontSize(self::getMaxFontSize($moonBold, $name, 760, 80));
        $nameTextLine->setFillColor($whitePixel);
        $image->annotateImage($nameTextLine, self::CARD_WIDTH_PX / 2, $namePosition, 0, $name);

        /* Add subtitle (school/company) */
        $subtitleTextLine = new ImagickDraw();
        $subtitleTextLine->setFont($moonLight);
        $subtitleTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $subtitleTextLine->setTextKerning(2);
        $subtitleTextLine->setFontSize(self::getMaxFontSize($moonLight, $card->subtitle, 760, 45));
        $subtitleTextLine->setFillColor($whitePixel);
        $image->annotateImage($subtitleTextLine, self::CARD_WIDTH_PX / 2, $namePosition + 60, 0, $card->subtitle);

        /* Add role */
        //white stripe BG
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
        $image->annotateImage($roleTextLine, self::CARD_WIDTH_PX / 2, 1120, 0, strtoupper($card->role));

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
}
