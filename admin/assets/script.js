(function ($) {

    $(document).ready(function () {

        $('#acpt_menu_icon').css({'width': '100%'}).select2({
            templateResult: select2_template,
            templateSelection: select2_template

        });

        $.each(acpt.conditional_logic.Items(), function (logicIndex, condition_sets) {

            var field_to_toggle = $('[data-field-key="' + condition_sets.key + '"]');

            $.each(condition_sets.args[0], function (logicIndex, condition) {

                condition_setup(field_to_toggle, condition);

            });

        });

        function condition_setup(field_to_toggle, condition) {

            var toggling_field = $('[data-field-key="' + condition.field + '"]');

            toggling_field.on('toggled', function (event, show) {

                field_to_toggle.toggleClass('parent-conditionally-hidden', !show);

            });

            var field_type = toggling_field.data('field-type');

            if ('true_false' === field_type) {

                var element_change = $(':checkbox', toggling_field);

                element_change.on('change', function () {

                    var show = null;

                    if ('==' === condition.operator) {

                        show = $(this).prop('checked') === ('1' === condition.value);
                    }
                    else if ('!=' === condition.operator) {

                        show = $(this).prop('checked') !== ('1' === condition.value);
                    }

                    field_to_toggle.toggleClass('conditionally-hidden', !show);

                    field_to_toggle.trigger('toggled', [show]);

                }).trigger('change');
            }

            if ('select' === field_type) {

                var element_change = $('select', toggling_field);

                if ('==' === condition.operator) {

                    element_change.on('change', function () {

                        var show = $(this).val() === condition.value;

                        field_to_toggle.toggleClass('conditionally-hidden', !show);

                        field_to_toggle.trigger('toggle', [show]);

                    }).trigger('change');

                }

                if ('!=' === condition.operator) {

                    element_change.on('change', function () {

                        var show = $(this).val() !== condition.value;

                        field_to_toggle.toggleClass('conditionally-hidden', !show);

                        field_to_toggle.trigger('toggle', [show]);

                    }).trigger('change');

                }
            }
        }

        $('.acpt-postbox .tabs > .tab').on('click', function () {

            var tab = $(this);

            tab.addClass('selected').siblings().removeClass('selected');

            var tab_contents = tab.closest('.acpt-postbox').find('.tab-content');

            tab_contents.eq(tab.index()).addClass('selected').siblings().removeClass('selected');

        });

        var
            plural_name_input = $('[name="acpt_plural_name"]'),
            singular_name_input = $('[name="acpt_singular_name"]'),
            auto_generate_checkbox = $('[name="acpt_auto_generate_labels"]'),
            label_inputs = $('[name^="acpt_label_"]');

        var new_acpt = !singular_name_input.val().length;

        plural_name_input.on('keyup', generate_titles);

        singular_name_input.on('keyup', function () {
            new_acpt = false;
            generate_titles();
        });

        auto_generate_checkbox.on('change', function () {

            generate_titles();

            label_inputs.prop('readonly', auto_generate_checkbox[0].checked);

        }).trigger('change');

        function generate_titles() {

            if (new_acpt) {
                singular_name_input.val(pluralize.singular(plural_name_input.val()));
            }

            if (!auto_generate_checkbox[0].checked) return;

            var names_values = label_patterns(singular_name_input.val(), plural_name_input.val());

            $.each(names_values, function (name, value) {
                $('[name="' + name + '"]').val(value).trigger('change');
            });
        }

        function label_patterns(_singular_name, _plural_name) {
            var
                singular_name_lowercase = _singular_name.toLowerCase(),
                plural_name_lowercase = _plural_name.toLowerCase(),
                singular_name = toTitleCase(singular_name_lowercase),
                plural_name = toTitleCase(plural_name_lowercase)
                ;

            return {
                'acpt_label_add_new': 'Add New',
                'acpt_label_add_new_item': 'Add New ' + singular_name,
                'acpt_label_edit_item': 'Edit ' + singular_name,
                'acpt_label_new_item': 'New ' + singular_name,
                'acpt_label_view_item': 'View ' + singular_name,
                'acpt_label_search_items': 'Search ' + plural_name,
                'acpt_label_not_found': 'No ' + plural_name_lowercase + ' found',
                'acpt_label_not_found_in_trash': 'No ' + plural_name_lowercase + ' found in Trash',
                'acpt_label_parent_item_colon': 'Parent ' + singular_name,
                'acpt_label_all_items': 'All ' + plural_name,
                'acpt_label_archives': plural_name + ' Archives',
                'acpt_label_insert_into_item': 'Insert into ' + singular_name_lowercase,
                'acpt_label_uploaded_to_this_item': 'Uploaded to this ' + singular_name_lowercase,
                'acpt_label_featured_image': 'Featured Image',
                'acpt_label_set_featured_image': 'Set featured image',
                'acpt_label_remove_featured_image': 'Remove featured image',
                'acpt_label_use_featured_image': 'Use as featured image',
                'acpt_label_menu_name': plural_name,
                'acpt_label_filter_items_list': plural_name,
                'acpt_label_items_list_navigation': plural_name,
                'acpt_label_items_list': plural_name,
                'acpt_label_name_admin_bar': singular_name
            };
        }

    });

    function select2_template(data) {

        if (!data.id) return data.text;

        return $('<span><i style="vertical-align: text-bottom;" class="dashicons ' + data.id.toLowerCase() + '"></i>&ensp;' + data.text + '</span>');
    }

    function toTitleCase(str) {
        return str.replace(/\w\S*/g, function (txt) {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    }

})
(jQuery);