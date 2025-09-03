<?php require_once('../../config/db.php'); ?>
<button class="close-btn" onclick="hidePanel()">✖</button>
<h2>📁 <span style="color: #0d47a1;">Category Manager</span></h2>

<!-- Toast Message -->
<div id="toast" class="toast hidden"></div>

<!-- Category Form -->
<form id="categoryForm">
  <input type="hidden" name="id" id="categoryId">
  <input type="text" name="name" id="categoryName" placeholder="Category Name" required>
  <input type="text" name="description" id="categoryDesc" placeholder="Description">
  <button type="submit">💾 Save</button>
</form>

<!-- Search Input -->
<input type="text" id="filterInput" placeholder="🔍 Search category..." style="padding:8px; border-radius:4px; margin-bottom:10px; width: 100%;">

<!-- Category List -->
<ul id="categoryList"></ul>
<div id="paginationControls" style="margin-top:10px; text-align:center;"></div>
<div id="paginationControls" style="margin-top:10px; text-align:center;"></div>

<script>
function initCategoryManager() {
  let allCategories = [];
  let filteredCategories = [];
  let currentPage = 1;
  const itemsPerPage = 5;

  const toast = document.getElementById('toast');
  const filterInput = document.getElementById('filterInput');
  const categoryForm = document.getElementById('categoryForm');
  const categoryList = document.getElementById('categoryList');
  const paginationControls = document.getElementById('paginationControls');

  function showToast(message, type = 'success') {
    toast.innerText = message;
    toast.className = `toast ${type}`;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
  }

  function fetchCategories() {
    fetch('/POS_UG/controllers/productController.php?action=getCategories')
      .then(res => res.json())
      .then(data => {
        allCategories = data || [];
        filteredCategories = [...allCategories];
        currentPage = 1;
        renderList();
      })
      .catch(err => {
        console.error('Error fetching:', err);
        showToast('❌ Failed to load categories', 'error');
      });
  }

  function renderList() {
    categoryList.innerHTML = '';
    const start = (currentPage - 1) * itemsPerPage;
    const paginated = filteredCategories.slice(start, start + itemsPerPage);

    if (paginated.length === 0) {
      categoryList.innerHTML = '<li>No categories found.</li>';
    } else {
      paginated.forEach(cat => {
        const li = document.createElement('li');
        li.innerHTML = `
          <span><strong>${cat.name}</strong> - <small>${cat.description ?? ''}</small></span>
          <div>
            <button onclick='editCategory(${JSON.stringify(cat)})'>✏️</button>
            <button onclick='deleteCategory(${cat.id})'>🗑️</button>
          </div>`;
        categoryList.appendChild(li);
      });
    }

    renderPagination();
  }

  function renderPagination() {
    const totalPages = Math.ceil(filteredCategories.length / itemsPerPage);
    paginationControls.innerHTML = '';

    if (totalPages <= 1) return;

    if (currentPage > 1) {
      paginationControls.innerHTML += `<button onclick="changeCategoryPage(${currentPage - 1})">⬅️ Previous</button>`;
    }

    paginationControls.innerHTML += ` <strong>Page ${currentPage} of ${totalPages}</strong> `;

    if (currentPage < totalPages) {
      paginationControls.innerHTML += `<button onclick="changeCategoryPage(${currentPage + 1})">Next ➡️</button>`;
    }
  }

  window.changeCategoryPage = function (page) {
    currentPage = page;
    renderList();
  };

  window.editCategory = function (cat) {
    document.getElementById('categoryId').value = cat.id;
    document.getElementById('categoryName').value = cat.name;
    document.getElementById('categoryDesc').value = cat.description ?? '';
  };

  window.deleteCategory = function (id) {
    if (!confirm('Are you sure you want to delete this category?')) return;

    fetch(`/POS_UG/controllers/productController.php?action=deleteCategory&id=${id}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('🗑️ Category deleted.', 'error');
          fetchCategories();
          categoryForm.reset();
        } else {
          showToast('❌ ' + data.error, 'error');
        }
      });
  };

  categoryForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    const id = formData.get('id');
    const action = id ? 'updateCategory' : 'addCategory';

    fetch(`/POS_UG/controllers/productController.php?action=${action}`, {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast(`✅ Category ${id ? 'updated' : 'added'} successfully.`);
          this.reset();
          fetchCategories();
        } else {
          showToast('❌ ' + data.error, 'error');
        }
      })
      .catch(err => {
        console.error('Save failed:', err);
        showToast('❌ Error saving category.', 'error');
      });
  });

  filterInput.addEventListener('input', () => {
    const query = filterInput.value.toLowerCase();
    filteredCategories = allCategories.filter(cat =>
      cat.name.toLowerCase().includes(query) ||
      (cat.description && cat.description.toLowerCase().includes(query))
    );
    currentPage = 1;
    renderList();
  });

  filterInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') e.preventDefault();
  });

  fetchCategories();
}

window.initCategoryManager = initCategoryManager;
</script>


<!-- ✅ Styling -->
<style>
.toast {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #0d47a1;
  color: white;
  padding: 12px 16px;
  border-radius: 6px;
  font-weight: bold;
  z-index: 9999;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  transition: all 0.3s ease-in-out;
}
.toast.error { background: #c62828; }
.toast.hidden { opacity: 0; pointer-events: none; }
.toast.success { background: #0d47a1; }

form#categoryForm {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}
form#categoryForm input {
  padding: 8px;
  border: 1px solid #bbb;
  border-radius: 4px;
}
form#categoryForm button {
  background: #0d47a1;
  color: white;
  padding: 10px;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  font-weight: bold;
}
form#categoryForm button:hover {
  background: #1565c0;
}

#categoryList {
  list-style: none;
  padding-left: 0;
}
#categoryList li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #eee;
}
#categoryList li span {
  display: inline-block;
  max-width: 70%;
}
#categoryList li div button {
  background: none;
  border: none;
  font-size: 16px;
  cursor: pointer;
}
#categoryList li div button:hover {
  opacity: 0.7;
}

#paginationControls button {
  padding: 5px 10px;
  margin: 5px;
  background-color: #0d47a1;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
#paginationControls button:hover {
  background-color: #1565c0;
}
</style>
