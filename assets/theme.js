// Theme and design scripts for HR2 system
// Example: Toggle sidebar
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  if (sidebar) {
    sidebar.classList.toggle('collapsed');
  }
}
// Example: Apply theme dynamically (future use)
function setThemeColor(color) {
  document.documentElement.style.setProperty('--primary-color', color);
}

// Client-side pagination for tables
let currentPage = 1;
let recordsPerPage = 15;
let allRows = [];
let filteredRows = [];

function initializePagination() {
  console.log('initializePagination called');
  
  // Check for both employeesTable and leavesTable
  let table = document.getElementById('employeesTable');
  if (!table) {
    table = document.getElementById('leavesTable');
  }
  
  console.log('Table found:', table);
  
  if (!table) return;
  
  const tbody = table.querySelector('tbody');
  allRows = Array.from(tbody.querySelectorAll('tr'));
  filteredRows = [...allRows];
  
  console.log('Found rows:', allRows.length);
  
  setupPaginationControls();
  displayPage(1);
  
  // Setup search functionality
  const searchInput = document.getElementById('search');
  
  console.log('Search input found:', searchInput);
  
  if (searchInput) {
    searchInput.addEventListener('input', filterAndPaginate);
  }
}

function filterAndPaginate() {
  const searchTerm = document.getElementById('search').value.toLowerCase();
  
  filteredRows = allRows.filter(row => {
    const cells = row.querySelectorAll('td');
    if (cells.length === 0) return false;
    
    // Search filter
    const searchText = Array.from(cells).map(cell => cell.textContent.toLowerCase()).join(' ');
    const matchesSearch = searchTerm === '' || searchText.includes(searchTerm);
    
    return matchesSearch;
  });
  
  currentPage = 1;
  setupPaginationControls();
  displayPage(1);
}

function displayPage(page) {
  currentPage = page;
  const startIndex = (page - 1) * recordsPerPage;
  const endIndex = startIndex + recordsPerPage;
  
  // Hide all rows first
  allRows.forEach(row => row.style.display = 'none');
  
  // Show rows for current page
  const rowsToShow = filteredRows.slice(startIndex, endIndex);
  rowsToShow.forEach(row => row.style.display = '');
  
  updatePaginationInfo();
  updatePaginationButtons();
  
  // Update page numbers to highlight current page
  const totalPages = Math.ceil(filteredRows.length / recordsPerPage);
  generatePageNumbers(totalPages);
}

function setupPaginationControls() {
  const totalPages = Math.ceil(filteredRows.length / recordsPerPage);
  const paginationControls = document.getElementById('pagination-controls');
  
  if (totalPages > 1) {
    paginationControls.style.display = 'block';
    generatePageNumbers(totalPages);
  } else {
    paginationControls.style.display = 'none';
  }
  
  // Update total pages display
  const totalPagesSpan = document.getElementById('total-pages');
  if (totalPagesSpan) {
    totalPagesSpan.textContent = totalPages;
  }
}

function generatePageNumbers(totalPages) {
  const pageNumbersContainer = document.getElementById('page-numbers');
  pageNumbersContainer.innerHTML = '';
  
  const maxVisiblePages = 5;
  let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
  let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
  
  // Adjust start page if we're near the end
  if (endPage - startPage < maxVisiblePages - 1) {
    startPage = Math.max(1, endPage - maxVisiblePages + 1);
  }
  
  // First page and ellipsis
  if (startPage > 1) {
    pageNumbersContainer.appendChild(createPageButton(1, '1'));
    if (startPage > 2) {
      pageNumbersContainer.appendChild(createEllipsis());
    }
  }
  
  // Page numbers
  for (let i = startPage; i <= endPage; i++) {
    pageNumbersContainer.appendChild(createPageButton(i, i.toString()));
  }
  
  // Last page and ellipsis
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      pageNumbersContainer.appendChild(createEllipsis());
    }
    pageNumbersContainer.appendChild(createPageButton(totalPages, totalPages.toString()));
  }
}

function createPageButton(pageNum, text) {
  const button = document.createElement('button');
  button.textContent = text;
  button.onclick = () => displayPage(pageNum);
  
  const baseClasses = 'px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200';
  if (pageNum === currentPage) {
    button.className = `${baseClasses} bg-blue-600 text-white`;
  } else {
    button.className = `${baseClasses} text-gray-700 bg-white border border-gray-300 hover:bg-gray-50`;
  }
  
  return button;
}

function createEllipsis() {
  const span = document.createElement('span');
  span.textContent = '...';
  span.className = 'px-3 py-2 text-sm font-medium text-gray-500';
  return span;
}

function updatePaginationInfo() {
  const startIndex = (currentPage - 1) * recordsPerPage + 1;
  const endIndex = Math.min(currentPage * recordsPerPage, filteredRows.length);
  
  // Update pagination info
  const paginationInfo = document.getElementById('pagination-info');
  if (paginationInfo) {
    paginationInfo.textContent = `Showing ${startIndex} to ${endIndex} of ${filteredRows.length} results`;
  }
  
  // Update page info
  const currentPageSpan = document.getElementById('current-page');
  const visibleRecordsSpan = document.getElementById('visible-records');
  
  if (currentPageSpan) {
    currentPageSpan.textContent = currentPage;
  }
  
  if (visibleRecordsSpan) {
    visibleRecordsSpan.textContent = endIndex - startIndex + 1;
  }
}

function updatePaginationButtons() {
  const totalPages = Math.ceil(filteredRows.length / recordsPerPage);
  const prevBtn = document.getElementById('prev-btn');
  const nextBtn = document.getElementById('next-btn');
  
  // Previous button
  if (prevBtn) {
    if (currentPage === 1) {
      prevBtn.disabled = true;
      prevBtn.className = 'px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed';
    } else {
      prevBtn.disabled = false;
      prevBtn.className = 'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-900 transition-colors duration-200';
    }
  }
  
  // Next button
  if (nextBtn) {
    if (currentPage === totalPages || totalPages === 0) {
      nextBtn.disabled = true;
      nextBtn.className = 'px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed';
    } else {
      nextBtn.disabled = false;
      nextBtn.className = 'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-900 transition-colors duration-200';
    }
  }
}

function previousPage() {
  if (currentPage > 1) {
    displayPage(currentPage - 1);
  }
}

function nextPage() {
  const totalPages = Math.ceil(filteredRows.length / recordsPerPage);
  if (currentPage < totalPages) {
    displayPage(currentPage + 1);
  }
}

function clearEmployeeFilters() {
  document.getElementById('search').value = '';
  filterAndPaginate();
}

// Add more reusable JS functions as needed
