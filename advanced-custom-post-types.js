(function ($)
{

    $(document).ready(function ()
    {

        var
            plural_name_input = $('[data-name="acpt_plural_name"] :input'),
            singular_name_input = $('[data-name="acpt_singular_name"] :input'),
            auto_generate_checkbox = $('[data-name="acpt_auto_generate_labels"] :checkbox'),
            label_inputs = $('[data-name^="acpt_label_"] :input');

        plural_name_input.on('keyup', generate_titles);

        singular_name_input.on('keyup', generate_titles);

        auto_generate_checkbox.on('change', function(){

            generate_titles();

            label_inputs.prop('readonly', auto_generate_checkbox[0].checked);

        }).trigger('change');

        function generate_titles()
        {
            if (!auto_generate_checkbox[0].checked) return;

            var names_values = label_patterns(singular_name_input.val(), plural_name_input.val());

            $.each(names_values, function (name, value)
            {
                $('[data-name="' + name + '"] :input').val(value).trigger('change');
            });
        }

        function label_patterns(_singular_name, _plural_name)
        {
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

    function toTitleCase(str)
    {
        return str.replace(/\w\S*/g, function (txt) {return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
    }

    function format(data)
    {
        if (!data.id) return data.text;

        return '<i style="vertical-align: middle;" class="dashicons ' + data.id.toLowerCase() + '"></i> ' + data.text;
    }

    acf.add_action('ready append', function ($el)
    {
        $('[data-name="acpt_menu_icon"] select').select2({
            width: '100%',
            formatResult: format,
            formatSelection: format,
            escapeMarkup: function (m)
            {
                return m;
            }
        });
    });

})(jQuery);