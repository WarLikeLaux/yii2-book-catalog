<?php

declare(strict_types=1);

namespace app\domain\exceptions;

enum DomainErrorCode: string
{
    case BookTitleEmpty = 'book.error.title_empty';
    case BookTitleTooLong = 'book.error.title_too_long';
    case BookIsbnChangePublished = 'book.error.isbn_change_published';
    case BookInvalidAuthorId = 'book.error.invalid_author_id';
    case BookNotFound = 'book.error.not_found';
    case BookPublishWithoutAuthors = 'book.error.publish_without_authors';
    case BookPublishWithoutCover = 'book.error.publish_without_cover';
    case BookPublishShortDescription = 'book.error.publish_short_description';
    case BookStaleData = 'book.error.stale_data';
    case BookIsbnExists = 'book.error.isbn_exists';

    case AuthorFioEmpty = 'author.error.fio_empty';
    case AuthorFioTooShort = 'author.error.fio_too_short';
    case AuthorFioTooLong = 'author.error.fio_too_long';
    case AuthorNotFound = 'author.error.not_found';
    case AuthorCreateFailed = 'author.error.create_failed';
    case AuthorUpdateFailed = 'author.error.update_failed';
    case AuthorFioExists = 'author.error.fio_exists';

    case SubscriptionAlreadySubscribed = 'subscription.error.already_subscribed';
    case SubscriptionCreateFailed = 'subscription.error.create_failed';

    case IsbnInvalidFormat = 'isbn.error.invalid_format';

    case YearTooOld = 'year.error.too_old';
    case YearFuture = 'year.error.future';

    case EntityAlreadyExists = 'error.entity_already_exists';

    case IdempotencyKeyInProgress = 'idempotency.error.key_in_progress';
    case IdempotencyStorageUnavailable = 'idempotency.error.storage_unavailable';

    case FileKeyInvalidFormat = 'file.error.key_invalid_format';
    case FileContentInvalidStream = 'file.error.content_invalid_stream';
    case FileFileNotFound = 'file.error.not_found';
    case FileOpenFailed = 'file.error.open_failed';
}
