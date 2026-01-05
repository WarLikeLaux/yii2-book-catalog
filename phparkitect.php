<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\HaveNameMatching;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\IsA;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\IsInterface;
use Arkitect\Expression\ForClasses\IsNotAbstract;
use Arkitect\Expression\ForClasses\IsNotInterface;
use Arkitect\Expression\ForClasses\IsNotTrait;
use Arkitect\Expression\ForClasses\IsReadonly;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotExtend;
use Arkitect\Expression\ForClasses\NotHaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $applicationSet = ClassSet::fromDir(__DIR__ . '/application');
    $domainSet = ClassSet::fromDir(__DIR__ . '/domain');
    $infrastructureSet = ClassSet::fromDir(__DIR__ . '/infrastructure');
    $presentationSet = ClassSet::fromDir(__DIR__ . '/presentation');

    $domainRules = [];
    $applicationRules = [];
    $infrastructureRules = [];
    $presentationRules = [];

    // --- Domain Layer ---

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\entities'))
        ->should(new IsFinal())
        ->because('Сущности должны быть final');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\entities'))
        ->should(new NotExtend('yii\db\ActiveRecord'))
        ->because('Сущности не должны зависеть от ActiveRecord');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\values'))
        ->should(new IsFinal())
        ->because('Value Objects должны быть final');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\values'))
        ->should(new IsReadonly())
        ->because('Value Objects должны быть readonly');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\events'))
        ->andThat(new IsNotInterface())
        ->should(new HaveNameMatching('*Event'))
        ->because('События должны иметь суффикс Event');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\events'))
        ->andThat(new IsNotInterface())
        ->should(new IsA('app\domain\events\DomainEvent'))
        ->because('События должны реализовывать DomainEvent');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\exceptions'))
        ->andThat(new IsNotInterface())
        ->andThat(new IsNotAbstract())
        ->andThat(new NotHaveNameMatching('DomainException'))
        ->should(new IsFinal())
        ->because('Конкретные исключения должны быть final');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\exceptions'))
        ->andThat(new IsNotInterface())
        ->andThat(new IsNotAbstract())
        ->andThat(new NotHaveNameMatching('DomainException'))
        ->should(new IsA('app\domain\exceptions\DomainException'))
        ->because('Доменные исключения должны наследоваться от DomainException');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain'))
        ->should(new NotDependsOnTheseNamespaces(['yii']))
        ->because('Домен не должен зависеть от фреймворка');


    // --- Application Layer ---

    $applicationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\application\*\usecases'))
        ->should(new IsFinal())
        ->because('UseCases должны быть final');

    $applicationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\application\*\usecases'))
        ->should(new HaveNameMatching('*UseCase'))
        ->because('UseCases должны иметь суффикс UseCase');

    $applicationRules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Command'))
        ->should(new IsFinal())
        ->because('Команды должны быть final');

    $applicationRules[] = Rule::allClasses()
        ->that(new HaveNameMatching('*Command'))
        ->should(new IsReadonly())
        ->because('Команды должны быть readonly');

    $applicationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\application\*\queries'))
        ->andThat(new IsNotInterface())
        ->should(new IsFinal())
        ->because('Query DTO должны быть final');

    $applicationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\application\*\queries'))
        ->andThat(new IsNotInterface())
        ->should(new IsReadonly())
        ->because('Query DTO должны быть readonly');

    $applicationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\application\ports'))
        ->should(new IsInterface())
        ->because('Порты должны быть интерфейсами');

    $applicationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\application\ports'))
        ->should(new HaveNameMatching('*Interface'))
        ->because('Порты должны иметь суффикс Interface');

    $applicationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\application'))
        ->should(new NotDependsOnTheseNamespaces(['yii']))
        ->because('Слой приложения не должен зависеть от фреймворка');


    // --- Infrastructure Layer ---

    $infrastructureRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\infrastructure\repositories'))
        ->andThat(new IsNotInterface())
        ->andThat(new IsNotTrait())
        ->should(new IsFinal())
        ->because('Реализации репозиториев должны быть final');

    $infrastructureRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\infrastructure\repositories'))
        ->andThat(new IsNotInterface())
        ->andThat(new IsNotTrait())
        ->should(new IsReadonly())
        ->because('Реализации репозиториев должны быть readonly');

    $infrastructureRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\infrastructure\repositories'))
        ->andThat(new IsNotInterface())
        ->andThat(new IsNotTrait())
        ->should(new Implement('app\application\ports\*'))
        ->because('Репозитории должны реализовывать порты');


    // --- Presentation Layer ---

    $presentationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\presentation\controllers'))
        ->should(new IsFinal())
        ->because('Контроллеры должны быть final');

    $presentationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\presentation\controllers'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('Контроллеры должны иметь суффикс Controller');


    $config
        ->add($domainSet, ...$domainRules)
        ->add($applicationSet, ...$applicationRules)
        ->add($infrastructureSet, ...$infrastructureRules)
        ->add($presentationSet, ...$presentationRules);
};
