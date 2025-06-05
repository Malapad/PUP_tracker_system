document.addEventListener('DOMContentLoaded', function() {
    const notificationLinkToggle = document.getElementById('notificationLinkToggle');
    const notificationsDropdownContent = document.getElementById('notificationsDropdownContent');

    if (notificationLinkToggle && notificationsDropdownContent) {
        notificationLinkToggle.addEventListener('click', function(event) {
            event.preventDefault();
            notificationsDropdownContent.classList.toggle('show');
        });
    }

    document.addEventListener('click', function(event) {
        if (notificationsDropdownContent && notificationLinkToggle) {
            if (!notificationLinkToggle.contains(event.target) && !notificationsDropdownContent.contains(event.target)) {
                notificationsDropdownContent.classList.remove('show');
            }
        }
    });

    const handbookSearchInputElem = document.getElementById('handbookSearchInput');
    if (handbookSearchInputElem) {
        handbookSearchInputElem.addEventListener('input', handbookSearch);
        
        const handbookSearchForm = document.getElementById('handbookSearchForm');
        if (handbookSearchForm) {
            handbookSearchForm.addEventListener('submit', function(event) {
                event.preventDefault();
                handbookSearch();
            });
        }
    }
});

function handbookSearch() {
    const searchTerm = document.getElementById('handbookSearchInput').value.toLowerCase();
    const list = document.getElementById('handbookViolationList');
    const listItems = list.querySelectorAll('li.handbook-item');
    let foundItems = 0;

    listItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            item.style.display = '';
            foundItems++;
        } else {
            item.style.display = 'none';
        }
    });

    let noResultsMessage = list.querySelector('.no-handbook-items-message.search-specific');
    const originalNoItemsMessage = list.querySelector('.no-handbook-items-message:not(.search-specific)');

    if (foundItems === 0 && searchTerm) {
        if (!noResultsMessage) {
            noResultsMessage = document.createElement('li');
            noResultsMessage.className = 'no-handbook-items-message search-specific';
            list.appendChild(noResultsMessage);
        }
        noResultsMessage.textContent = 'No violation types match your search.';
        noResultsMessage.style.display = '';
        if (originalNoItemsMessage) originalNoItemsMessage.style.display = 'none';
    } else {
        if (noResultsMessage) {
            noResultsMessage.style.display = 'none';
        }
        if (!searchTerm && originalNoItemsMessage && listItems.length === 0) {
             originalNoItemsMessage.style.display = '';
        } else if (!searchTerm && originalNoItemsMessage && listItems.length > 0) {
            originalNoItemsMessage.style.display = 'none';
        }
    }
}

  // Accordion behavior: allow only one open at a time
  document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
      const currentItem = header.parentElement;
      const allItems = document.querySelectorAll('.accordion-item');

      allItems.forEach(item => {
        if (item !== currentItem) {
          item.classList.remove('active');
        }
      });

      currentItem.classList.toggle('active');
    });
  });

  // Modal elements
  const modal = document.getElementById('violation-details-modal');
  const closeModal = document.getElementById('close-modal');

  // Close modal on clicking the close button or outside the modal content
  closeModal.addEventListener('click', () => {
    modal.style.display = 'flex';
  });

  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.style.display = 'none';
    }
  });

  // Show modal with violation details
  document.querySelectorAll('.violation-type-item').forEach(item => {
    item.addEventListener('click', function () {
      const typeId = this.dataset.id;

      fetch('fetch_violation_details.php?id=' + encodeURIComponent(typeId))
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('detail-category').textContent = data.category;
            document.getElementById('detail-type').textContent = data.type;

            const tbody = document.getElementById('sanctions-table-body');
            tbody.innerHTML = '';
            data.sanctions.forEach(row => {
              const tr = document.createElement('tr');
              tr.innerHTML = `<td>${row.offense_level}</td><td>${row.sanction}</td>`;
              tbody.appendChild(tr);
            });

            modal.style.display = 'flex';
          } else {
            alert('Violation details not found.');
          }
        })
        .catch(error => {
          console.error('Fetch error:', error);
          alert('Error fetching violation details.');
        });
    });
  });