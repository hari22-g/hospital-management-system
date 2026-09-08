$(document).ready(function () {
  // Event listener for the health link (Health Tracker)
  $("#loadDashboard").on("click", function (e) {
    e.preventDefault();

    // Load health content via AJAX
    $.ajax({
      url: "p_dashoboard.php", //
      type: "POST",
      success: function (data) {
        $("#dynamicContent").html(data);
      },
      error: function () {
        $("#dynamicContent").html("<p>Error loading dashboard.</p>");
      },
    });

    // Remove 'selected-item' class from all nav links and add to the clicked link
    $(".nav-link").removeClass("selected-item");
    $(this).addClass("selected-item");
  });

  // Event listener for the Profile link
  $("#loadProfile").on("click", function (e) {
    e.preventDefault();
    // Load Profile content via AJAX
    $.ajax({
      url: "p_profile.php", // This file will contain the profile content
      type: "POST",
      success: function (data) {
        $("#dynamicContent").html(data);
      },
    });

    // Remove 'selected-item' class from all nav links and add to the clicked link
    $(".nav-link").removeClass("selected-item");
    $(this).addClass("selected-item");
  });

  // Event listener for the Report link
  $("#loadReport").on("click", function (e) {
    e.preventDefault();
    // Load Report content via AJAX
    $.ajax({
      url: "p_report.php", // This file will contain the Report content
      type: "POST",
      success: function (data) {
        $("#dynamicContent").html(data);
      },
    });

    // Remove 'selected-item' class from all nav links and add to the clicked link
    $(".nav-link").removeClass("selected-item");
    $(this).addClass("selected-item");
  });
});
