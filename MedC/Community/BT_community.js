// Tabs functionality
const tabButtons = document.querySelectorAll(".tab-btn");
const tabContents = document.querySelectorAll(".tab-content");

tabButtons.forEach((button) => {
  button.addEventListener("click", () => {
    // Remove active class from all buttons and contents
    tabButtons.forEach((btn) => btn.classList.remove("active"));
    tabContents.forEach((content) => content.classList.remove("active"));

    // Add active class to the clicked button and corresponding content
    button.classList.add("active");
    document.getElementById(button.dataset.tab).classList.add("active");
  });
});

// Post actions
document.querySelectorAll(".fa-comment").forEach((icon) => {
  icon.addEventListener("click", function () {
    // Find the closest parent post and then find the comments section within it
    const post = this.closest(".post");
    const comments = post.querySelector(".comments");
    // Toggle the display of the comments section
    comments.style.display =
      comments.style.display === "block" ? "none" : "block";
  });
});



// Post actions - Toggle Comments Section
document.addEventListener("click", function (event) {
  if (event.target.classList.contains("fa-comment")) {
      console.log("Comment button clicked!"); // Debugging log

      let post = event.target.closest(".post"); // Find the closest post
      console.log("Post found:", post); // Debugging log

      if (!post) {
          console.error("No .post parent found! Check your HTML structure.");
          return;
      }

      let comments = post.querySelector(".comments"); // Find the comments section
      console.log("Comments section:", comments); // Debugging log

      if (comments) {
          // Toggle visibility
          comments.style.display = comments.style.display === "block" ? "none" : "block";
      } else {
          console.error("Comments section not found in post.");
      }
  }
});
