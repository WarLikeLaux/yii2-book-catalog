(function($) {
    const DataGenerator = {
        isbn: function() {
            return faker.book.isbn();
        },
        title: function() {
            return faker.book.title();
        },
        year: function() {
            return faker.date.past(50).getFullYear();
        },
        description: function() {
            return faker.lorem.paragraph();
        },
        fio: function() {
            return faker.name.fullName();
        }
    };

    $(document).ready(function() {
        if (typeof faker === 'undefined') {
            $('[data-action="generate-data"]')
                .css({ 'opacity': 0.6, 'cursor': 'not-allowed' })
                .attr('title', 'Библиотека Faker не загружена');
        }

        if (typeof bootstrap !== 'undefined') {
            $('[data-bs-toggle="tooltip"]').each(function() {
                new bootstrap.Tooltip(this);
            });
        }

        $('body').on('click', '[data-action="generate-data"]', function() {
            if (typeof faker === 'undefined') return;

            const btn = $(this);
            const type = btn.data('type');
            const targetSelector = btn.data('target');
            const input = $(targetSelector);

            if (input.length && DataGenerator[type]) {
                input.val(DataGenerator[type]()).trigger('change');
            }
        });
    });
})(jQuery);

