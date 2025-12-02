<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\db\Query;
use yii\web\Controller;

final class ReportController extends Controller
{
    public function actionIndex(): string
    {
        $year = (int)($this->request->get('year') ?: date('Y'));

        $topAuthors = (new Query())
            ->select([
                'a.id',
                'a.fio',
                'COUNT(DISTINCT b.id) as books_count',
            ])
            ->from(['a' => 'authors'])
            ->innerJoin(['ba' => 'book_authors'], 'ba.author_id = a.id')
            ->innerJoin(['b' => 'books'], 'b.id = ba.book_id')
            ->where(['b.year' => $year])
            ->groupBy(['a.id', 'a.fio'])
            ->orderBy(['books_count' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('index', [
            'topAuthors' => $topAuthors,
            'year' => $year,
        ]);
    }
}

