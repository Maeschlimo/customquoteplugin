jQuery(document).ready(
    function($) {
         // Ensure plugin_object is defined
        if (typeof plugin_object === "undefined" || !plugin_object.ajax_url) {
            console.error("❌ Error: plugin_object is not defined. Make sure it is localized in functions.php.");
            return;
        }
        $(window).on('load', function() {
            var input_value = $("input[name=quantity]").val();
            $("input[name=product_quantity]").val(input_value);
          //  alert(input_value);
        });
        $('input[name=quantity]').change(function() {
            var input_value = $("input[name=quantity]").val();
        	//alert(input_value);
           $('input[name=product_quantity]').val(input_value);
        });
        $(".cart_to_quote_submit").on("click", function(event) {
            event.preventDefault(); // Prevent default form submission
    
            $.ajax({
                type: "POST",
                url: plugin_object.ajax_url,
                data: {
                    action: "handle_quote_submission",
                    security: plugin_object.security // Include nonce for security
                },
                success: function(response) {
                    window.location.href = "/quote"; // Redirect to quote page
                },
                error: function(xhr, status, error) {
                    console.error("❌ Error submitting quote:", error);
                }
            });
        });
        // Function to remove an item from the quote
    $(document).on("click", ".product-remove", function() {
        var productId = $(this).attr("id").replace("product_", ""); // Extract ID from the element
        var lengthMm = $(this).data("length") || ""; // Get length if available
        var ajaxurl = plugin_object.ajax_url; // Ensure this is localized in WordPress

        console.log("🗑 Removing Product ID:", productId, "Length (if any):", lengthMm);

        // Check if productId is valid before proceeding
        if (!productId || productId.trim() === "") {
            console.error("❌ Error: No valid product ID found.");
            return;
        }

        // Send AJAX request to remove the item
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: "quote_remove",
                product_id: productId,
                length_mm: lengthMm
            },
            success: function(response) {

                // Remove the row from the table
                $("#product_" + productId).closest("tr").fadeOut(300, function() {
                    $(this).remove();
                });

                // If the quote is empty, refresh the page or show an empty message
                if ($(".shop_table.cart tbody tr").length === 1) {
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                console.error("❌ Error removing item:", error);
            }
        });
    });

		_remove_th_on_responsive();
		change_size();
		$(window).resize(
			function() {
				_remove_th_on_responsive();
				change_size();
			}
		);
		
		function _remove_th_on_responsive() {
			if($(window).width() <= 768){
				$('.quote table thead th.product-remove').text(' ');
				$('.quote table thead th.product-thumbnail').text(' ');
			}
			else {
				$('.quote table thead th.product-remove').text('Remove');
				$('.quote table thead th.product-thumbnail').text('Product Image');
			}
		}

		$('#_email_quote_trigger').click(
			function() {
				$('#_send_quote_email_')[0].click();
			}
		);

		$('#send_trigger').click(
			function() {
				var $toSend = $('#_to_send_email').val();
				$('._to_send_email').val($toSend);
				$('.quote_data_wrapper ._submit').click();
			}
		);

		$('#waqt_user_quote_detail').find('._tab_menu_option').click(
			function() {

				$(this).parents('._table_content_wrapper').siblings().find('._tab_accordian_panel').removeClass('active');
				$(this).parents('._table_content_wrapper').siblings().find('._tab_accordian_panel').slideUp();
				if($(this).parents('._table_content_wrapper').find('._tab_accordian_panel').hasClass('active')) {
					$(this).parents('._table_content_wrapper').find('._tab_accordian_panel').removeClass('active');
					$(this).parents('._table_content_wrapper').find('._tab_accordian_panel').slideUp();
				}
				else {
					$(this).parents('._table_content_wrapper').find('._tab_accordian_panel').addClass('active');
					$(this).parents('._table_content_wrapper').find('._tab_accordian_panel').slideDown();
				}
			}
		);

		function change_size(){
			var $target = $('._quoteall_buttons_wrapper');
			var $buttons_wrapper_width = $target.width();
			var $window_width = $(window).width();

			if($buttons_wrapper_width < 550 && $window_width < 950){
				$target.addClass('small_width');
			}
			else if($buttons_wrapper_width > 550 && $window_width > 950) {
				$target.removeClass('small_width');
			}
		}
    }
);

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}
jQuery(document).ready(function($) {
    function validateQuoteVariation(event) {
        console.log("validateQuoteVariation function triggered"); 
        var variationSelected = true;

        // Get product type from the form
        var productType = $("#_add_to_quote_form_wrapper input.product_type").val();
        console.log("DEBUG: Checking product type in validation:", productType);

        // Skip validation if the product is NOT a variable product
        if (productType !== "variable") {
            console.log("Skipping variation validation for simple product.");
            return true;
        }

        // Otherwise, proceed with variation validation
        $('.variations_form').each(function() {
            var form = $(this);
            var allSelected = true;

            form.find('.variations select').each(function() {
                var selectedValue = $(this).val();
                if (!selectedValue || selectedValue === "" || selectedValue.toLowerCase() === "choose an option") {
                    allSelected = false;
                    $(this).css("border", "2px solid red");
                } else {
                    $(this).css("border", "");
                }
            });

            var variationId = form.find('input.variation_id').val().trim();
            console.log("DEBUG: Variation ID:", variationId);
            if (!variationId || variationId === "0" || variationId === "") {
                allSelected = false;
            }

            if (!allSelected) {
                variationSelected = false;
            }
        });

        if (!variationSelected) {
            alert("Please select all required product variations before adding to quote.");
            event.preventDefault();
            return false;
        }

        return true;
    }

    // Attach validation to the quote form submission
    $("#_add_to_quote_form_wrapper").on("submit", function(event) {
        return validateQuoteVariation(event);
    });
});


jQuery(document).ready(function($) {
	
    function updateQuoteForm() {
        var selectedVariationId = $(".single-product form.variations_form input.variation_id").val();
        var selectedAttributes = $(".single-product form.variations_form select").serialize();
        var lengthValue = $(".single-product input#length_mm").val(); // Get length_mm input value

        // Update hidden fields inside the quote form
        $("#_add_to_quote_form_wrapper input.variation_id").val(selectedVariationId);
        $("#_add_to_quote_form_wrapper input.variations_attr").val(selectedAttributes);
        $("#_add_to_quote_form_wrapper input[name='length_mm']").val(lengthValue);

        // Debugging output
        console.log("Updated variation_id:", selectedVariationId);
        console.log("Updated variations_attr:", selectedAttributes);
        console.log("Updated length_mm:", lengthValue);
    }

    // Update when a variation is selected
    $(".single-product form.variations_form select").on("change", function() {
        setTimeout(updateQuoteForm, 300);
    });

    // Update when the length_mm field is changed
    $(".single-product input#length_mm").on("input change", function() {
        updateQuoteForm();
    });

    // Ensure hidden inputs are updated before form submission
    $("#_add_to_quote_form_wrapper").on("submit", function(event) {
        updateQuoteForm();
        console.log("Submitting form, length_mm:", $("#_add_to_quote_form_wrapper input[name='length_mm']").val());
    });
});

jQuery(document).ready(function($) {

    function updateQuoteForm() {
        var selectedVariationId = $(".single-product form.variations_form input.variation_id").val();
        var selectedAttributes = $(".single-product form.variations_form select").serialize();
        var lengthValue = $('input[name="length_mm"]').val(); // Get length_mm input value

        // Ensure hidden fields inside the quote form are updated
        var quoteForm = $("#_add_to_quote_form_wrapper");
        quoteForm.find("input.variation_id").val(selectedVariationId);
        quoteForm.find("input.variations_attr").val(selectedAttributes);
        quoteForm.find("input[name='length_mm']").val(lengthValue);

        // Debugging output
        console.log("✅ Updated variation_id:", selectedVariationId);
        console.log("✅ Updated variations_attr:", selectedAttributes);
        console.log("✅ Updated length_mm:", lengthValue);
    }

    // **Use Event Delegation to Ensure Updates Work Even After Submission**
    $(document).on("change", ".single-product form.variations_form select", function() {
        setTimeout(updateQuoteForm, 300);
    });

    $(document).on("input change", 'input[name="length_mm"]', function() {
        updateQuoteForm();
    });

    // **Ensure Hidden Inputs Are Updated Before Form Submission**
    $(document).on("submit", "#_add_to_quote_form_wrapper", function(event) {
        updateQuoteForm();
        console.log("🚀 Submitting form, length_mm:", $("#_add_to_quote_form_wrapper input[name='length_mm']").val());
    });

    // **Ensure Updates Work After AJAX Reloads**
    $(document).ajaxComplete(function() {
        updateQuoteForm(); // Ensures form updates after AJAX requests
        console.log("AJAX content loaded, reapplying updates.");
    });

});



jQuery(document).ready(function($) {
    if (window.location.href.includes("quote")) {
        console.log("Disabling Avada price calculations on Quote page.");
        return;
    }
});
