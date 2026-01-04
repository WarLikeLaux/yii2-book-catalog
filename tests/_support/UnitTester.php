<?php

// phpcs:ignoreFile
// NOTE: Файл игнорируется из-за фатальной ошибки в Slevomat Sniffs (Undefined array key "scope_closer")

declare(strict_types=1);

use _generated\UnitTesterActions;
use Codeception\Actor;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class UnitTester extends Actor
{
    use UnitTesterActions;
}
