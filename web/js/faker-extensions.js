(function() {
    if (typeof faker !== 'undefined') {
        faker.locale = 'ru';

        faker.book = {
            title: function() {
                const adj = ['Забытый', 'Вечный', 'Тайный', 'Великий', 'Программируемый', 'Античный', 'Новый', 'Полный', 'Красный', 'Зеленый', 'Сложный', 'Простой', 'Современный', 'Идеальный', 'Безумный', 'Легендарный', 'Скрытый', 'Последний', 'Невероятный'];
                const nouns = ['код', 'алгоритм', 'путь', 'справочник', 'мир', 'метод', 'анализ', 'секрет', 'замок', 'лес', 'океан', 'проект', 'язык', 'фреймворк', 'рефакторинг', 'деплой', 'монолит', 'микросервис', 'баг', 'релиз', 'паттерн'];
                const suffixes = ['судьбы', 'времени', 'данных', 'для чайников', 'на практике', 'в 21 веке', 'и его друзья', 'во тьме', 'с нуля', 'безумия', 'будущего', 'безысходности', 'на выходных', 'в продакшене', 'без регистрации', 'глазами джуна', 'для сеньоров', 'через боль'];
                
                const r = (arr) => arr[Math.floor(Math.random() * arr.length)];
                return `${r(adj)} ${r(nouns)} ${r(suffixes)}`;
            },
            isbn: function() {
                let isbn = '978';
                for (let i = 0; i < 9; i++) {
                    isbn += Math.floor(Math.random() * 10);
                }
                let sum = 0;
                for (let i = 0; i < 12; i++) {
                    sum += parseInt(isbn[i]) * (i % 2 === 0 ? 1 : 3);
                }
                let checkDigit = (10 - (sum % 10)) % 10;
                if (checkDigit === 10) checkDigit = 0;
                return isbn + checkDigit;
            }
        };

        faker.name.fullName = function() {
            const gender = Math.floor(Math.random() * 2);
            return `${faker.name.lastName(gender)} ${faker.name.firstName(gender)} ${faker.name.middleName(gender)}`;
        };
    }
})();
