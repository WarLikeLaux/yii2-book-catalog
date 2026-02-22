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
use Arkitect\Expression\ForClasses\IsNotEnum;
use Arkitect\Expression\ForClasses\IsNotInterface;
use Arkitect\Expression\ForClasses\IsNotTrait;
use Arkitect\Expression\ForClasses\IsReadonly;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotExtend;
use Arkitect\Expression\ForClasses\NotHaveNameMatching;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $applicationSet = ClassSet::fromDir(__DIR__ . '/src/application');
    $domainSet = ClassSet::fromDir(__DIR__ . '/src/domain');
    $infrastructureSet = ClassSet::fromDir(__DIR__ . '/src/infrastructure');
    $presentationSet = ClassSet::fromDir(__DIR__ . '/src/presentation');

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
        ->andThat(new IsNotEnum())
        ->should(new IsFinal())
        ->because('Value Objects должны быть final');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\values'))
        ->andThat(new IsNotEnum())
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
        ->should(new NotDependsOnTheseNamespaces(['yii', 'app\application', 'app\infrastructure', 'app\presentation']))
        ->because('Домен изолирован: запрещены зависимости от yii, application, infrastructure, presentation');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\repositories'))
        ->should(new IsInterface())
        ->because('domain/repositories содержит только интерфейсы репозиториев');

    $domainRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\domain\repositories'))
        ->should(new HaveNameMatching('*RepositoryInterface'))
        ->because('Интерфейсы репозиториев должны иметь суффикс RepositoryInterface');

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
        ->that(new ResideInOneOfTheseNamespaces('app\application\*\queries'))
        ->should(new NotDependsOnTheseNamespaces(['app\infrastructure']))
        ->because('application/*/queries — DTO-only: без сервисов и инфраструктуры');

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
        ->andThat(new IsNotAbstract())
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
        ->andThat(new IsNotAbstract())
        ->should(new Implement('app\domain\repositories\*'))
        ->because('Репозитории должны реализовывать порты домена');

    // --- Presentation Layer ---

    $presentationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\presentation\controllers'))
        ->andThat(new IsNotAbstract())
        ->should(new IsFinal())
        ->because('Контроллеры должны быть final (кроме абстрактных)');

    $presentationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\presentation\controllers'))
        ->should(new HaveNameMatching('*Controller'))
        ->because('Контроллеры должны иметь суффикс Controller');

    $presentationRules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('app\presentation'))
        ->should(new NotDependsOnTheseNamespaces(['app\domain\entities']))
        ->because('Слой представления не должен зависеть от сущностей домена напрямую. Используйте DTO или Value Objects.');

    $config
        ->add($domainSet, ...$domainRules)
        ->add($applicationSet, ...$applicationRules)
        ->add($infrastructureSet, ...$infrastructureRules)
        ->add($presentationSet, ...$presentationRules);
};
