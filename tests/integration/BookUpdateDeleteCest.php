<?php

declare(strict_types=1);

use app\application\books\usecases\UpdateBookUseCase;
use app\application\common\exceptions\ApplicationException;
use app\domain\exceptions\DomainErrorCode;
use app\domain\exceptions\StaleDataException;
use app\domain\values\BookStatus;
use app\infrastructure\persistence\Author;
use app\infrastructure\persistence\Book;
use app\infrastructure\persistence\User;
use app\presentation\books\dto\BookEditViewModel;
use app\presentation\books\dto\BookViewModel;
use app\presentation\books\forms\BookForm;
use app\presentation\books\handlers\BookItemViewFactory;
use Codeception\Stub;

final class BookUpdateDeleteCest
{
    private const ROUTE_BOOK_UPDATE = 'book/update';
    public function _before(IntegrationTester $I): void
    {
        DbCleaner::clear(['book_authors', 'books', 'authors']);
        $I->amLoggedInAs(User::findByUsername('admin'));
    }

    public function testUpdateBookPage(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Test Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Book To Update',
            'year' => 2024,
            'isbn' => '9783161484100',
            'description' => 'Test',
        ]);
        Yii::$app->db->createCommand()
            ->insert('book_authors', ['book_id' => $bookId, 'author_id' => $authorId])
            ->execute();

        $I->amOnRoute(self::ROUTE_BOOK_UPDATE, ['id' => $bookId]);
        $I->seeResponseCodeIs(200);
        $I->see(Yii::t('app', 'ui.book_update'));
        $I->seeInField('BookForm[title]', 'Book To Update');
    }

    public function testUpdateBookSuccess(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Update Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Original Title',
            'year' => 2024,
            'isbn' => '9783161484100',
        ]);

        $I->amOnRoute(self::ROUTE_BOOK_UPDATE, ['id' => $bookId]);
        $I->fillField('BookForm[title]', 'Updated Title');
        $I->fillField('BookForm[year]', '2025');

        $I->sendPost('/index-test.php?r=book/update&id=' . $bookId, [
            'BookForm' => [
                'title' => 'Updated Title',
                'year' => '2025',
                'isbn' => '9783161484100',
                'authorIds' => [$authorId],
            ],
        ]);

        $I->seeInCurrentUrl('book');
        $I->seeInCurrentUrl('view');
        $I->see('Updated Title');
        $I->seeRecord(Book::class, ['id' => $bookId, 'title' => 'Updated Title', 'year' => 2025]);
    }

    public function testDeleteBook(IntegrationTester $I): void
    {
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Book To Delete',
            'year' => 2024,
            'isbn' => '9780134685991',
            'description' => 'Will be deleted',
        ]);

        $I->sendAjaxPostRequest('/index-test.php?r=book/delete&id=' . $bookId);
        $I->dontSeeRecord(Book::class, ['id' => $bookId]);
    }

    public function testUpdateBookStaleData(IntegrationTester $I): void
    {
        $authorId = $I->haveRecord(Author::class, ['fio' => 'Stale Data Author']);
        $bookId = $I->haveRecord(Book::class, [
            'title' => 'Original Title',
            'year' => 2024,
            'isbn' => '9783161484100',
            'version' => 1,
        ]);

        $mockUseCase = Stub::make(UpdateBookUseCase::class, [
            'execute' => static function () {
                throw ApplicationException::fromDomainException(
                    new StaleDataException(
                        DomainErrorCode::BookStaleData,
                    ),
                );
            },
        ]);

        $mockViewFactory = Stub::make(BookItemViewFactory::class, [
            'getBookForUpdate' => static fn() => new BookForm(),
            'getUpdateViewModel' => static fn ($id, $form) => new BookEditViewModel(
                $form,
                [],
                new BookViewModel(
                    $id,
                    'Title',
                    2024,
                    '',
                    '978-3-16-148410-0',
                    [],
                    null,
                    BookStatus::Draft->value,
                ),
            ),
        ]);

        Yii::$container->set(UpdateBookUseCase::class, $mockUseCase);
        Yii::$container->set(BookItemViewFactory::class, $mockViewFactory);

        try {
            $I->amOnRoute(self::ROUTE_BOOK_UPDATE, ['id' => $bookId]);
            $I->sendPost('/index-test.php?r=book/update&id=' . $bookId, [
                'BookForm' => [
                    'title' => 'New Title',
                    'year' => '2024',
                    'isbn' => '9783161484100',
                    'authorIds' => [$authorId],
                    'version' => 1,
                ],
            ]);

            $I->seeResponseCodeIs(200);
            $I->see(Yii::t('app', 'book.error.stale_data'));
        } finally {
            Yii::$container->clear(UpdateBookUseCase::class);
            Yii::$container->clear(BookItemViewFactory::class);
        }
    }
}
