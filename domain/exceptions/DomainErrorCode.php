<?php

declare(strict_types=1);

namespace app\domain\exceptions;

enum DomainErrorCode: string
{
    #[ErrorMapping(ErrorType::OperationFailed, field: 'title')]
    case BookTitleEmpty = 'book.error.title_empty';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'title')]
    case BookTitleTooLong = 'book.error.title_too_long';

    #[ErrorMapping(ErrorType::BusinessRule, field: 'isbn')]
    case BookIsbnChangePublished = 'book.error.isbn_change_published';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'authorIds')]
    case BookInvalidAuthorId = 'book.error.invalid_author_id';

    #[ErrorMapping(ErrorType::NotFound)]
    case BookNotFound = 'book.error.not_found';

    #[ErrorMapping(ErrorType::BusinessRule)]
    case BookPublishWithoutAuthors = 'book.error.publish_without_authors';

    #[ErrorMapping(ErrorType::BusinessRule)]
    case BookPublishWithoutCover = 'book.error.publish_without_cover';

    #[ErrorMapping(ErrorType::BusinessRule)]
    case BookPublishShortDescription = 'book.error.publish_short_description';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case BookStaleData = 'book.error.stale_data';

    #[ErrorMapping(ErrorType::AlreadyExists, field: 'isbn')]
    case BookIsbnExists = 'book.error.isbn_exists';

    #[ErrorMapping(ErrorType::NotFound, field: 'authorIds')]
    case BookAuthorsNotFound = 'book.error.authors_not_found';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'fio')]
    case AuthorFioEmpty = 'author.error.fio_empty';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'fio')]
    case AuthorFioTooShort = 'author.error.fio_too_short';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'fio')]
    case AuthorFioTooLong = 'author.error.fio_too_long';

    #[ErrorMapping(ErrorType::NotFound)]
    case AuthorNotFound = 'author.error.not_found';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case AuthorCreateFailed = 'author.error.create_failed';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'fio')]
    case AuthorUpdateFailed = 'author.error.update_failed';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case AuthorStaleData = 'author.error.stale_data';

    #[ErrorMapping(ErrorType::AlreadyExists, field: 'fio')]
    case AuthorFioExists = 'author.error.fio_exists';

    #[ErrorMapping(ErrorType::AlreadyExists)]
    case SubscriptionAlreadySubscribed = 'subscription.error.already_subscribed';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case SubscriptionCreateFailed = 'subscription.error.create_failed';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case SubscriptionStaleData = 'subscription.error.stale_data';

    #[ErrorMapping(ErrorType::BusinessRule)]
    case AuthInvalidCredentials = 'auth.error.invalid_credentials';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'isbn')]
    case IsbnInvalidFormat = 'isbn.error.invalid_format';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'year')]
    case YearTooOld = 'year.error.too_old';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'year')]
    case YearFuture = 'year.error.future';

    #[ErrorMapping(ErrorType::AlreadyExists)]
    case EntityAlreadyExists = 'error.entity_already_exists';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case IdempotencyKeyInProgress = 'idempotency.error.key_in_progress';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case IdempotencyStorageUnavailable = 'idempotency.error.storage_unavailable';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'key')]
    case FileKeyInvalidFormat = 'file.error.key_invalid_format';

    #[ErrorMapping(ErrorType::OperationFailed, field: 'content')]
    case FileContentInvalidStream = 'file.error.content_invalid_stream';

    #[ErrorMapping(ErrorType::NotFound)]
    case FileNotFound = 'file.error.not_found';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case FileOpenFailed = 'file.error.open_failed';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case FileStorageOperationFailed = 'file.error.storage_operation_failed';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case EntityDeleteFailed = 'error.entity_delete_failed';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case EntityPersistFailed = 'error.entity_persist_failed';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case EntityIdMissing = 'error.entity_id_missing';

    #[ErrorMapping(ErrorType::OperationFailed)]
    case MapperFailed = 'error.mapper_failed';
}
