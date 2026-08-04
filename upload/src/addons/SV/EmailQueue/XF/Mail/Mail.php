<?php

namespace SV\EmailQueue\XF\Mail;

use SV\StandardLib\Helper;
use SV\EmailQueue\XF\Mail\XF22\MailPatch as MailPatchXF22;

/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
Helper::repo()->aliasClass(
    Mail::class,
    \XF::$versionId < 2030000
        ? MailPatchXF22::class
        : MailPatch::class,
    \XF\Mail\Mail::class
);
