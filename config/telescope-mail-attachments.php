<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Store Attachment Content
    |--------------------------------------------------------------------------
    |
    | When enabled, the full base64-encoded content of each attachment will be
    | stored in the Telescope entry. This allows downloading attachments from
    | the Telescope UI. Disable this if you only want metadata (filename,
    | size, mime type) to reduce storage usage.
    |
    */

    'store_content' => env('TELESCOPE_MAIL_ATTACHMENTS_STORE_CONTENT', true),

];
