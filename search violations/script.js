const searchInput = document.getElementById("searchInput");
const listItems = document.querySelectorAll("#violationList li");

searchInput.addEventListener("input", () => {
  const searchTerm = searchInput.value.toLowerCase();

  listItems.forEach(item => {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(searchTerm) ? "list-item" : "none";
  });
});
