<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

/** @deprecated Use WrapLongChainsRector to wrap method and property chains together. */
final class WrapLongFluentChainsRector extends WrapLongChainsRector
{
    protected const CHAIN_KIND = 'methods';
}
