var ajax_url = ced_mcfw_pricing_obj.ajax_url;
var ajax_nonce = ced_mcfw_pricing_obj.ajax_nonce;
let mode = ced_mcfw_pricing_obj.mode;
// var URLs = jQuery(location).attr("href");
// console.log(URls);
// scrollTop = window.pageYOffset || document.documentElement.scrollTop;
// (scrollLeft = window.pageXOffset || document.documentElement.scrollLeft),
//   // if any scroll is attempted,
//   // set this to the previous value
//   (window.onscroll = function () {
//     window.scrollTo(scrollLeft, scrollTop);
//   });
jQuery(document).ready(function () {
  const searchParams = new URLSearchParams(window.location.search);
  const myParam = searchParams.get("is_update");
  if (myParam) {
    var x = jQuery(window).scrollTop();
    jQuery("html,body").animate({ scrollTop: x + 500 });
    jQuery("#ced-main-pricing")
      .addClass("ced-display-block")
      .removeClass("ced-display-none");
  }
});
jQuery(document).on("click", ".ced_final_checkout", function (e) {
  e.preventDefault();

  var plan_type = jQuery(this).attr("data-planName");
  var contract_id = jQuery(this).attr("data-contract_id");
  var count = jQuery(this).attr("data-count");
  var plan_period = jQuery("input[name=switch]:checked").val();
  let coupon_code = jQuery("input[name=coupon_code]").val();

  let marketplaces = [];
  jQuery("input:checkbox[name=selected-marketplaces]:checked").each(
    (key, element) => {
      marketplaces.push(element.value);
    }
  );
  jQuery("#wpbody-content").block({
    message: null,
    overlayCSS: {
      background: "#fff",
      opacity: 0.6,
    },
  });

  jQuery.ajax({
    url: ajax_url,
    type: "post",
    data: {
      ajax_nonce: ajax_nonce,
      plan_type: plan_type,
      contract_id: contract_id,
      plan_period: plan_period,
      coupon_code: coupon_code,
      count: count,
      selected_marketplace: marketplaces,
      mode:mode,
      action: "ced_woo_pricing_plan_selection",
    },
    success: function (response) {
      jQuery("#wpbody-content").unblock();

      var parsed_response = jQuery.parseJSON(response);
      let errorOccured = 0;

      if (!parsed_response || parsed_response.status == "400") {
        errorOccured = 1;
      } else {
        var confirmation_url = parsed_response.confirmation_url
          ? parsed_response.confirmation_url
          : "";
        if (confirmation_url != "") {
          
          window.location.href = confirmation_url;
        } else {
          errorOccured = 1;
        }
      }

      if (errorOccured) {
        window.scrollTo(0, 0);
        let title = "Plan checkout failed!";
        let text = parsed_response.message;

        let notice =
          "<div  class='notice notice-error'><p> <b>" +
          title +
          "</b>. " +
          text +
          " </p></div>";

        if (jQuery(".notice-error").length == 0) {
          jQuery("#wpbody-content").prepend(notice);
        } else {
          jQuery("#wpbody-content")
            .find(".notice-error")
            .html("<p><b>" + title + "</b>. " + text + "</p>");
        }

        setTimeout(() => {
          jQuery("#wpbody-content").find(".notice").remove();
          //window.location.reload();
        }, 5000);
      }
    },
  });
});

const ced_reset_coupon = (
  previous_cost = "",
  container = false,
  plan_type = ""
) => {
  jQuery("input[name=coupon_code]").val("");
  jQuery(".ced_add_coupon_link_div").hide();
  jQuery("#ced_previous_price").html("");
  if (previous_cost != "") {
    jQuery("#ced_checkout_total").html("$" + previous_cost);
    jQuery("#ced_total_plan_name").html("/" + plan_type);
  }
  jQuery(".ced_add_coupon_form_div").hide();
  jQuery(".ced_remove_coupon_div").hide();
  jQuery("#ced_coupon_error").html("");
  jQuery(".ced_add_coupon_link_div").show();

  if (container) jQuery(".ced-bottom-cart-container").hide();
};

// const ced_reset_checkout_div=()=>{
//   jQuery(".ced_add_coupon_form_div").hide();
//   jQuery(".ced_remove_coupon_div").hide();
//   jQuery("#ced_checkout_total").html("");
// }

jQuery(document).on("click", ".woo_ced_plan_selection_button", function (e) {
  e.preventDefault();
  ced_reset_coupon();
  jQuery('.woo_ced_plan_selection_button').removeClass('clicked_plan')
  jQuery(this).addClass('clicked_plan');
  let plan_name = jQuery(this).attr("data-plan_name");
  let plan_type = jQuery(this).attr("data-plan_type");
  let contract_id = jQuery(this).attr("data-contract_id");
  let count = jQuery(this).attr("data-count");
  let plan_cost = jQuery(this).attr(
    "data-final_cost-" + plan_name.toLowerCase()
  );
  var marketplace_names = [];
  jQuery("input:checkbox.select-marketplace").each(function () {
    if (this.checked) {
      marketplace_names.push(jQuery(this).val());
    }
  });
  console.log("marketplace_names", marketplace_names);

  jQuery("#ced_checkout_total").html("$" + plan_cost);
  jQuery("#ced_total_plan_name").html("/" + plan_type);
  jQuery("#ced_show_plan_name").html("(" + plan_name + "/" + plan_type + ")");
  jQuery(".ced-bottom-cart-container").show();
  jQuery(".validate_coupon").attr("data-planName", plan_name);
  jQuery(".ced_final_checkout").attr("data-planName", plan_name);
  jQuery(".validate_coupon").attr("data-planCost", plan_cost);
  jQuery(".ced_remove_coupon").attr("data-planCost", plan_cost);
  jQuery(".ced_remove_coupon").attr("data-planType", plan_type);
});

jQuery(document).on("click", "#ced_add_coupon", function (e) {
  jQuery(".ced_add_coupon_link_div").hide();
  jQuery(".ced_add_coupon_form_div").show();
  jQuery("#ced_coupon_error").html("");
  jQuery("input[name=coupon_code]").val("");
});

jQuery(document).on("click", ".ced_remove_coupon", function (e) {
  let previous_cost = jQuery(this).attr("data-planCost");
  let plan_type = jQuery(this).attr("data-planType");
  ced_reset_coupon(previous_cost, "", plan_type);
});

jQuery(document).on("change", "input[name=switch]", function (e) {
  var plan_type = jQuery("input[name=switch]:checked").val();
  window.location.href += "&plan_type=" + plan_type;
});
jQuery(document).on("click", ".select-marketplace", function () {
  // jQuery(".ced_add_coupon_link_div").show();
  // jQuery(".ced_add_coupon_form_div").hide();
  jQuery('.woo_ced_plan_selection_button').removeClass('clicked_plan')
  ced_reset_coupon("", true, "");
  let checkcount = jQuery(".select-marketplace:checked").length;

  let marketplace_name = jQuery(this).val();
  //alert(marketplace_name);
  var plan_type = jQuery("input[name=switch]:checked").val();
  if (checkcount == 0) {
    return false;
  }
  jQuery("#wpbody-content").block({
    message: null,
    overlayCSS: {
      background: "#fff",
      opacity: 0.6,
    },
  });
  jQuery.ajax({
    url: ajax_url,
    type: "post",
    data: {
      ajax_nonce: ajax_nonce,
      checkcount: checkcount,
      plan_type: plan_type,
      marketplace_name: marketplace_name,
      mode:mode,
      action: "ced_woo_check_marketplaces",
    },
    success: function (response) {
      let data = jQuery.parseJSON(response);
      let basic_price = data.basic_price;
      let advance_price = data.advance_price;
      let final_basic_price = data.final_basic_price;
      let final_advance_price = data.final_advance_price;
      jQuery("#ced-price-basic").html("$" + basic_price);
      jQuery("#ced-price-advanced").html("$" + advance_price);
      jQuery("#ced_billed_annualy-basic").html(final_basic_price);
      jQuery("#ced_billed_annualy-advanced").html(final_advance_price);
      jQuery(".woo_ced_plan_selection_button").attr("data-count", checkcount);
      jQuery(".ced_final_checkout").attr("data-count", checkcount);
      jQuery("#ced-cost-basic").attr("data-plan_cost-basic", basic_price);
      jQuery("#ced-cost-advanced").attr(
        "data-plan_cost-advanced",
        advance_price
      );
      jQuery("#ced-cost-basic").attr(
        "data-final_cost-basic",
        final_basic_price
      );
      jQuery("#ced-cost-advanced").attr(
        "data-final_cost-advanced",
        final_advance_price
      );
      jQuery("#wpbody-content").unblock();
    },
  });
  //console.log(countCheckedCheckboxes);
});

jQuery(document).on("click", ".validate_coupon", function (e) {
  e.preventDefault();
  let plan_type = jQuery(this).attr("data-planName");
  let contract_id = jQuery(this).attr("data-contract_id");
  let checkcount = jQuery(".select-marketplace:checked").length;
  let plan_period = jQuery("input[name=switch]:checked").val();
  let coupon_code = jQuery("input[name=coupon_code]").val();
  jQuery("input[name=coupon_code]").css("border", "2px solid black");

  if ("" == coupon_code) {
    jQuery("input[name=coupon_code]").css("border", "2px solid red");
    return;
  }
  // alert(count);

  jQuery("#wpbody-content").block({
    message: null,
    overlayCSS: {
      background: "#fff",
      opacity: 0.6,
    },
  });

  jQuery.ajax({
    url: ajax_url,
    type: "post",
    data: {
      ajax_nonce: ajax_nonce,
      plan_type: plan_type,
      contract_id: contract_id,
      plan_period: plan_period,
      coupon_code: coupon_code,
      count: checkcount,
      mode:mode,
      action: "ced_woo_validate_coupon",
    },
    success: function (response) {
      console.log(response);
      jQuery("#wpbody-content").unblock();
      var response = jQuery.parseJSON(response);
      // var message         = parsed_response.message;
      if (response.status == "200") {
        let cost = response.cost;
        let previous_cost = response.previous_cost;
        // let previous_cost = jQuery(".validate_coupon").attr("data-planCost");
        jQuery(".ced_remove_coupon_div").show();
        jQuery("#ced_checkout_total").html("$" + cost);
        jQuery("#ced_previous_price").html("$" + previous_cost);
        jQuery("#ced_total_plan_name").html("/" + plan_period);
        jQuery(".ced_add_coupon_form_div").hide();
        jQuery(".coupon_message").html(
          response.message + " (" + coupon_code + ") "
        );
      } else {
        jQuery("#ced_coupon_error").html(response.message);
        jQuery(".ced_add_coupon_link_div").show();
        jQuery(".ced_add_coupon_form_div").hide();
        jQuery("input[name=coupon_code]").val('');
      }
    },
  });
  //console.log(countCheckedCheckboxes);
});

jQuery(document).on("click", ".ced-change-plan", function (e) {
  e.preventDefault();
  window.location.href += "&is_update=true";
});

jQuery(document).on("click", ".ced-cancel-current-plan", function (e) {
  e.preventDefault();
  if (
    confirm(
      "Are you sure you want to cancel the ongoing plan?\n \n Note : You can still use the plan for the remaining subscribed days."
    )
  ) {
    var contract_id = jQuery(this).attr("data-contract_id");

    jQuery("#wpbody-content").block({
      message: null,
      overlayCSS: {
        background: "#fff",
        opacity: 0.6,
      },
    });

    jQuery.ajax({
      url: ajax_url,
      type: "post",
      data: {
        ajax_nonce: ajax_nonce,
        contract_id: contract_id,
        mode:mode,
        action: "ced_woo_pricing_plan_cancellation",
      },
      success: function (response) {
        console.log(response);
        jQuery("#wpbody-content").unblock();
        var response = jQuery.parseJSON(response);
        // var message         = parsed_response.message;

        window.scrollTo(0, 0);

        let title;
        let text;

        if (response.status == "200") {
          title = "Plan cancelled";
          text = "Your plan has been successfully cancelled.";
        } else {
          title = "Plan cancellation failed!";
          text = "Your plan has been successfully cancelled.";
        }

        let notice =
          "<div  class='notice notice-success'><p> <b>" +
          title +
          "</b>. " +
          text +
          " </p></div>";

        if (jQuery(".notice-success").length == 0) {
          jQuery("#wpbody-content").prepend(notice);
        } else {
          jQuery("#wpbody-content")
            .find(".notice-success")
            .html("<p><b>" + title + "</b>. " + text + "</p>");
        }

        setTimeout(() => {
          jQuery("#wpbody-content").find(".notice").remove();
          window.location.reload();
        }, 3000);
      },
    });
  } else {
    return;
  }
});

jQuery(document).on("click", ".ced-update-current-plan", function (e) {
  // var d = jQuery("#ced-main-pricing")[0];
  // console.log(d);
  // d.class += "otherclass";
  window.history.replaceState(
    null,
    null,
    "?page=sales_channel&channel=pricing&is_update=true"
  );
  var x = jQuery(window).scrollTop();
  jQuery("html, body").animate({ scrollTop: x + 500 });

  // jQuery("html, body").animate(
  //   {
  //     scrollTop: jQuery("#wpbody-content").offset().top,
  //   },
  //   12000
  // );
  jQuery("#ced-main-pricing")
    .addClass("ced-display-block")
    .removeClass("ced-display-none");
});
