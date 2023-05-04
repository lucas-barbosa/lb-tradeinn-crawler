(function ($) {
  'use strict';

  $(document).ready(() => {
    function deleteRow() {
      const row = $(this).parents('tr');
      row.remove();
    }

    function renderRow(min = '', max = '', price = '') {
      const element = `
      <tr>
        <td>
          <input class="lb-weight-input" type="number" min="0" name="_min_weight[]" value="${min}">
          <span>gramas</span>
        </td>

        <td><b>até</b></td>

        <td>
          <input class="lb-weight-input" type="number" min="0" name="_max_weight[]" value="${max}">
          <span>gramas</span>
        </td>

        <td>
          <b>EUR</b>
          <input class="lb-weight-input" type="number" min="0" step="any" name="_min_price[]" value="${price}">
        </td>

        <td><button class="lb-weight-delete" type="button">Deletar</button>
      </tr>
      `;

      $('#lb-tradeinn-weight-settings tbody').append(element);
    }

    function renderTableData() {
      if (lb_tradeinn_crawler && lb_tradeinn_crawler.weight_settings) {
        if (lb_tradeinn_crawler.weight_settings.length > 0) {
          lb_tradeinn_crawler.weight_settings.map(el => renderRow(el.min_weight, el.max_weight, el.min_price));
          return;
        }
      }

      renderRow();
    }

    $(document).on('click', '#lb-tradeinn-new-line', renderRow);
    $(document).on('click', '.lb-weight-delete', deleteRow);

    const selectedCategories = lb_tradeinn_crawler.selected_categories || [];

    function renderCategory(id, name, parent = '', value = '', title = true, hasChilds = true) {
      if (!value) value = id;

      const element = `
        <li>
          <label id="lb-tradeinn-item_${id}">
            <input
              type="checkbox"
              name="selected_tradeinn_categories[]"
              value="${value}"
              ${value && selectedCategories.includes(value) ? 'checked' : ''}
              ${title ? `class="lb-tradeinn-title"` : ''}
            >${name}

            ${title && hasChilds ? `<button class="lb-tradeinn-toggle" type="button">Exibir/Ocultar</button>` : ''}
          </label>

          ${title && hasChilds ? `<ul class="lb-tradeinn-subitems"></ul>` : ''}
        </li>
      `;

      $(`#lb-tradeinn-available-categories ${parent}`).append(element);
    }

    const slugify = str =>
      str
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');

    function renderAvailableCategories() {
      if (lb_tradeinn_crawler && lb_tradeinn_crawler.available_categories) {
        const categories = lb_tradeinn_crawler.available_categories;

        const renderCategories = (items, parentSlug, parentValue) => {
          items.map(item => {
            const slug = `${parentSlug}${!!parentSlug ? '-' : ''}${slugify(item.name)}`;
            const hasChilds = item.childs && item.childs.length > 0 ? true : false;
            const parent = !parentSlug ? '' : `#lb-tradeinn-item_${parentSlug} ~ .lb-tradeinn-subitems`;

            renderCategory(slug, item.name, parent, !hasChilds ? `${parentValue}|${item.id}` : '', hasChilds, hasChilds);

            if (hasChilds) renderCategories(item.childs, slug, !parentValue ? `${item.name}|${item.id}` : `${parentValue}|${item.id}`);
          });
        };


        if (!categories.length) {
          $('#lb-tradeinn-save-categories').prop('disabled', true);
          return;
        }

        renderCategories(categories, '', '');

        $('.lb-tradeinn-subitems').slideToggle();
      } else {
        $('#lb-tradeinn-save-categories').prop('disabled', true);
      }
    }

    function selectAll() {
      $(this).parent().parent().find('.lb-tradeinn-subitems li label[id^="lb-tradeinn-item"] input').prop('checked', this.checked);
    }

    function toggle() {
      $(this).parent().parent().children('.lb-tradeinn-subitems').slideToggle();
    }

    $(document).on('change', '.lb-tradeinn-title', selectAll);
    $(document).on('click', '.lb-tradeinn-toggle', toggle);

    renderTableData();
    renderAvailableCategories();
  });
})(jQuery);