<?php

namespace SV\EmailQueue\XF\Mail;

use SV\StandardLib\Helper;
use SV\EmailQueue\XF\Mail\XF22\MailPatch as MailPatchXF22;

Helper::repo()->aliasClass(
    'SV\EmailQueue\XF\Mail',
    \XF::$versionId < 2030000
        ? MailPatchXF22::class
        : MailPatch::class
);
