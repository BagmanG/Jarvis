document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const tasksContainer = document.getElementById('tasks-container');
    const emptyState = document.getElementById('empty-state');
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const currentFilterElement = document.getElementById('current-filter');
    
    // Modals and forms
    const addTaskModal = new bootstrap.Modal(document.getElementById('addTaskModal'));
    const taskDetailsModal = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
    const taskForm = document.getElementById('task-form');
    const taskDateSelect = document.getElementById('task-date');
    const customDateContainer = document.getElementById('custom-date-container');
    
    // Buttons
    const saveTaskButton = document.getElementById('save-task');
    const deleteTaskButton = document.getElementById('delete-task');
    const toggleStatusButton = document.getElementById('toggle-status');
    
    // Filter links
    const filterLinks = document.querySelectorAll('.nav-link[data-filter]');
    
    // State
    let tasks = JSON.parse(localStorage.getItem('tasks')) || [];
    let currentFilter = 'today';
    let currentSearch = '';
    let currentTaskId = null;
    
    // Initialize the app
    init();
    
    function init() {
        renderTasks();
        updateProgress();
        setupEventListeners();
    }
    
    function setupEventListeners() {
        // Filter links
        filterLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.getAttribute('data-filter');
                setCurrentFilter(filter);
            });
        });
        
        // Search functionality
        searchButton.addEventListener('click', handleSearch);
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });
        
        // Task date select change
        taskDateSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateContainer.style.display = 'block';
            } else {
                customDateContainer.style.display = 'none';
            }
        });
        
        // Save task button
        saveTaskButton.addEventListener('click', saveTask);
        
        // Delete task button
        deleteTaskButton.addEventListener('click', deleteTask);
        
        // Toggle status button
        toggleStatusButton.addEventListener('click', toggleTaskStatus);
    }
    
    function setCurrentFilter(filter) {
        currentFilter = filter;
        currentSearch = '';
        searchInput.value = '';
        
        // Update active link
        filterLinks.forEach(link => {
            if (link.getAttribute('data-filter') === filter) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
        
        // Update current filter text
        switch(filter) {
            case 'today':
                currentFilterElement.textContent = 'Сегодня';
                break;
            case 'tomorrow':
                currentFilterElement.textContent = 'Завтра';
                break;
            case 'week':
                currentFilterElement.textContent = 'На неделю';
                break;
            case 'all':
                currentFilterElement.textContent = 'Все задачи';
                break;
        }
        
        renderTasks();
    }
    
    function handleSearch() {
        currentSearch = searchInput.value.trim().toLowerCase();
        renderTasks();
    }
    
    function saveTask() {
        const title = document.getElementById('task-title').value.trim();
        const description = document.getElementById('task-description').value.trim();
        const priority = document.getElementById('task-priority').value;
        const dateType = document.getElementById('task-date').value;
        
        if (!title) {
            alert('Пожалуйста, введите название задачи');
            return;
        }
        
        let dueDate;
        const now = new Date();
        
        switch(dateType) {
            case 'today':
                dueDate = formatDate(now);
                break;
            case 'tomorrow':
                const tomorrow = new Date(now);
                tomorrow.setDate(tomorrow.getDate() + 1);
                dueDate = formatDate(tomorrow);
                break;
            case 'week':
                const nextWeek = new Date(now);
                nextWeek.setDate(nextWeek.getDate() + 7);
                dueDate = formatDate(nextWeek);
                break;
            case 'custom':
                const customDate = document.getElementById('task-custom-date').value;
                if (!customDate) {
                    alert('Пожалуйста, выберите дату');
                    return;
                }
                dueDate = customDate;
                break;
        }
        
        const newTask = {
            id: Date.now().toString(),
            title,
            description,
            dueDate,
            priority,
            completed: false,
            createdAt: new Date().toISOString()
        };
        
        tasks.push(newTask);
        saveTasksToLocalStorage();
        renderTasks();
        updateProgress();
        addTaskModal.hide();
        taskForm.reset();
        customDateContainer.style.display = 'none';
    }
    
    function deleteTask() {
        tasks = tasks.filter(task => task.id !== currentTaskId);
        saveTasksToLocalStorage();
        renderTasks();
        updateProgress();
        taskDetailsModal.hide();
    }
    
    function toggleTaskStatus() {
        tasks = tasks.map(task => {
            if (task.id === currentTaskId) {
                return {...task, completed: !task.completed};
            }
            return task;
        });
        
        saveTasksToLocalStorage();
        renderTasks();
        updateProgress();
        taskDetailsModal.hide();
    }
    
    function openTaskDetails(taskId) {
        currentTaskId = taskId;
        const task = tasks.find(t => t.id === taskId);
        
        if (!task) return;
        
        document.getElementById('detail-title').textContent = task.title;
        document.getElementById('detail-description').textContent = task.description || 'Нет описания';
        
        const dueDate = new Date(task.dueDate);
        document.getElementById('detail-date').textContent = dueDate.toLocaleDateString('ru-RU', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
        
        let priorityText = '';
        switch(task.priority) {
            case 'low':
                priorityText = 'Низкий';
                break;
            case 'medium':
                priorityText = 'Средний';
                break;
            case 'high':
                priorityText = 'Высокий';
                break;
        }
        document.getElementById('detail-priority').textContent = priorityText;
        
        document.getElementById('detail-status').textContent = task.completed ? 'Выполнено' : 'Не выполнено';
        
        toggleStatusButton.textContent = task.completed ? 'Отметить как невыполненное' : 'Отметить как выполненное';
        
        taskDetailsModal.show();
    }
    
    function renderTasks() {
        const filteredTasks = filterTasks();
        
        if (filteredTasks.length === 0) {
            emptyState.style.display = 'block';
            tasksContainer.style.display = 'none';
        } else {
            emptyState.style.display = 'none';
            tasksContainer.style.display = 'flex';
            tasksContainer.innerHTML = '';
            
            filteredTasks.forEach(task => {
                const taskElement = createTaskElement(task);
                tasksContainer.appendChild(taskElement);
            });
        }
    }
    
    function filterTasks() {
        const now = new Date();
        const today = formatDate(now);
        
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = formatDate(tomorrow);
        
        const nextWeek = new Date(now);
        nextWeek.setDate(nextWeek.getDate() + 7);
        const nextWeekStr = formatDate(nextWeek);
        
        let filtered = tasks;
        
        // Apply filter
        if (currentFilter !== 'all') {
            filtered = tasks.filter(task => {
                if (currentFilter === 'today') {
                    return task.dueDate === today;
                } else if (currentFilter === 'tomorrow') {
                    return task.dueDate === tomorrowStr;
                } else if (currentFilter === 'week') {
                    return task.dueDate <= nextWeekStr && task.dueDate >= today;
                }
                return true;
            });
        }
        
        // Apply search
        if (currentSearch) {
            filtered = filtered.filter(task => 
                task.title.toLowerCase().includes(currentSearch) || 
                (task.description && task.description.toLowerCase().includes(currentSearch)));
        }
        
        // Sort by completed status and date
        filtered.sort((a, b) => {
            if (a.completed !== b.completed) {
                return a.completed ? 1 : -1;
            }
            return new Date(a.dueDate) - new Date(b.dueDate);
        });
        
        return filtered;
    }
    
    function createTaskElement(task) {
        const dueDate = new Date(task.dueDate);
        const formattedDate = dueDate.toLocaleDateString('ru-RU', {
            day: 'numeric',
            month: 'long'
        });
        
        let priorityClass = '';
        let priorityText = '';
        
        switch(task.priority) {
            case 'low':
                priorityClass = 'priority-low';
                priorityText = 'Низкий';
                break;
            case 'medium':
                priorityClass = 'priority-medium';
                priorityText = 'Средний';
                break;
            case 'high':
                priorityClass = 'priority-high';
                priorityText = 'Высокий';
                break;
        }
        
        const taskElement = document.createElement('div');
        taskElement.className = `col-md-6 col-lg-4 ${task.completed ? 'completed' : ''}`;
        taskElement.innerHTML = `
            <div class="card task-card">
                <div class="card-body">
                    <h5 class="card-title">${task.title}</h5>
                    <p class="card-text">${task.description || ''}</p>
                    <div class="task-meta">
                        <span class="task-date">${formattedDate}</span>
                        <span class="task-priority ${priorityClass}">${priorityText}</span>
                    </div>
                    <div class="task-actions mt-3">
                        <button class="btn btn-sm btn-outline-primary view-details" data-id="${task.id}">
                            <i class="fas fa-eye me-1"></i>Просмотр
                        </button>
                        ${task.completed ? `
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check me-1"></i>Выполнено
                            </span>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        taskElement.querySelector('.view-details').addEventListener('click', () => {
            openTaskDetails(task.id);
        });
        
        return taskElement;
    }
    
    function updateProgress() {
        const totalTasks = tasks.length;
        const completedTasks = tasks.filter(task => task.completed).length;
        const progress = totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;
        
        progressBar.style.width = `${progress}%`;
        progressText.textContent = `Завершено ${completedTasks} из ${totalTasks} задач`;
    }
    
    function saveTasksToLocalStorage() {
        localStorage.setItem('tasks', JSON.stringify(tasks));
    }
    
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
});