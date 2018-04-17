<?php
require_once('_bootstrap.php');
use RingCentral\SDK\SDK;

readVoicemail();

function readVoicemail(){
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
    $rcsdk = null;
    if (getenv('DEV_MODE') == "sandbox") {
        $rcsdk = new SDK(getenv('CLIENT_ID_SB'),
            getenv('CLIENT_SECRET_SB'), RingCentral\SDK\SDK::SERVER_SANDBOX);
    }else{
        $rcsdk = new SDK(getenv('CLIENT_ID_PROD'),
            getenv('CLIENT_SECRET_PROD'), RingCentral\SDK\SDK::SERVER_PRODUCTION);
    }
    $platform = $rcsdk->platform();
    try {
        if (getenv('DEV_MODE') == "sandbox")
            $platform->login(getenv('USERNAME_SB'), null, getenv('PASSWORD_SB'));
        else
            $platform->login(getenv('USERNAME_PROD'), null, getenv('PASSWORD_PROD'));

        try {
            $less60Days = time() - (86400 * 180);
            $dateFrom = date('Y-m-d', $less60Days)."T00:00:00.000Z";
            $response = $platform->get(
                '/account/~/extension/~/message-store',
                array(
                    'messageType' => 'VoiceMail',
                    'dateFrom' => $dateFrom
                ));
            $records = $response->json()->records;
            if (count($records) > 0) {
                foreach ($records as $record){
                    if ($record->attachments != null) {
                        print ($record->id . "\r\n");
                        foreach ($record->attachments as $attachment) {
                            $extension = ".mp3";
                            if ($attachment->contentType == "audio/wav")
                                $extension = ".wav";
                            else if ($attachment->contentType == "text/plain")
                                $extension = ".txt";
                            $apiResponse = $platform->get($attachment->uri);
                            $fileName = $record->id.$extension;
                            file_put_contents($fileName, $apiResponse->raw());
                            sleep(1);
                        }
                    }
                }
            }else {
               print("You have no voicemail.");
            }
        }catch (\RingCentral\SDK\Http\ApiException $e) {
            print($e);
        }
    }catch (\RingCentral\SDK\Http\ApiException $e) {
        print($e);
    }
}