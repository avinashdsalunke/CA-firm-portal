/* public/js/main.js */

document.addEventListener('DOMContentLoaded', () => {
    // CSRF and Ajax helper
    window.App = {
        toast: function(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast-msg toast-${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        },
        
        openModal: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
            }
        },
        
        closeModal: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        },
        
        validateFile: function(inputElement, allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'], maxSizeMB = 10) {
            const file = inputElement.files[0];
            if (!file) return true;
            
            const sizeMB = file.size / (1024 * 1024);
            if (sizeMB > maxSizeMB) {
                this.toast(`File is too large. Max allowed size is ${maxSizeMB}MB.`, 'error');
                inputElement.value = '';
                return false;
            }
            
            const filename = file.name;
            const ext = filename.split('.').pop().toLowerCase();
            if (!allowedExtensions.includes(ext)) {
                this.toast(`Invalid file format. Allowed formats: ${allowedExtensions.join(', ')}`, 'error');
                inputElement.value = '';
                return false;
            }
            
            return true;
        }
    };

    // Auto-attach modal toggle behaviors
    document.querySelectorAll('[data-open-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-open-modal');
            App.openModal(modalId);
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-close-modal');
            App.closeModal(modalId);
        });
    });

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.style.display = 'none';
            }
        });
    });

    // Handle file input validations automatically
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', () => {
            App.validateFile(input);
        });
    });

    // Dashboard Specific Logic
    window.Dashboard = {
        initCharts: function(revenueData, clientData) {
            const revCtx = document.getElementById('revenueChart');
            if (revCtx) {
                if (typeof Chart !== 'undefined') {
                    new Chart(revCtx, {
                        type: 'line',
                        data: {
                            labels: revenueData.labels,
                            datasets: [
                                {
                                    label: 'Collected (₹)',
                                    data: revenueData.collected,
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                                    fill: true,
                                    tension: 0.3
                                },
                                {
                                    label: 'Invoiced (₹)',
                                    data: revenueData.invoiced,
                                    borderColor: '#6366f1',
                                    backgroundColor: 'rgba(99, 102, 241, 0.05)',
                                    fill: true,
                                    tension: 0.3
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { labels: { color: '#f5f5f7' } }
                            },
                            scales: {
                                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a0b0' } },
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a0b0' } }
                            }
                        }
                    });
                } else {
                    Dashboard.renderOfflineRevenueSVG(revCtx, revenueData);
                }
            }

            const clientCtx = document.getElementById('clientGrowthChart');
            if (clientCtx) {
                if (typeof Chart !== 'undefined') {
                    new Chart(clientCtx, {
                        type: 'bar',
                        data: {
                            labels: clientData.labels,
                            datasets: [{
                                label: 'New Clients Joined',
                                data: clientData.counts,
                                backgroundColor: '#a855f7',
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { labels: { color: '#f5f5f7' } }
                            },
                            scales: {
                                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a0b0' } },
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a0b0' } }
                            }
                        }
                    });
                } else {
                    Dashboard.renderOfflineClientsSVG(clientCtx, clientData);
                }
            }
        },

        renderOfflineRevenueSVG: function(revCtx, revenueData) {
            const container = revCtx.parentElement;
            container.innerHTML = '';
            
            const width = container.clientWidth > 0 ? container.clientWidth : 500;
            const height = container.clientHeight > 0 ? container.clientHeight : 250;
            const paddingLeft = 50;
            const paddingRight = 20;
            const paddingTop = 30;
            const paddingBottom = 40;
            
            const chartWidth = width - paddingLeft - paddingRight;
            const chartHeight = height - paddingTop - paddingBottom;
            
            const maxVal = Math.max(...revenueData.collected, ...revenueData.invoiced, 1000);
            
            const pointsCollected = [];
            const pointsInvoiced = [];
            const steps = revenueData.labels.length;
            
            for (let i = 0; i < steps; i++) {
                const x = paddingLeft + (i / (steps - 1)) * chartWidth;
                const yCol = paddingTop + chartHeight - (revenueData.collected[i] / maxVal) * chartHeight;
                pointsCollected.push(`${x},${yCol}`);
                
                const yInv = paddingTop + chartHeight - (revenueData.invoiced[i] / maxVal) * chartHeight;
                pointsInvoiced.push(`${x},${yInv}`);
            }
            
            let svg = `<svg width="100%" height="100%" viewBox="0 0 ${width} ${height}">
                <line x1="${paddingLeft}" y1="${paddingTop}" x2="${width - paddingRight}" y2="${paddingTop}" stroke="rgba(255,255,255,0.05)" />
                <line x1="${paddingLeft}" y1="${paddingTop + chartHeight * 0.5}" x2="${width - paddingRight}" y2="${paddingTop + chartHeight * 0.5}" stroke="rgba(255,255,255,0.05)" />
                <line x1="${paddingLeft}" y1="${paddingTop + chartHeight}" x2="${width - paddingRight}" y2="${paddingTop + chartHeight}" stroke="rgba(255,255,255,0.1)" />
                
                <text x="${paddingLeft - 10}" y="${paddingTop + 4}" fill="#a0a0b0" font-size="10" text-anchor="end">₹${(maxVal/1000).toFixed(0)}k</text>
                <text x="${paddingLeft - 10}" y="${paddingTop + chartHeight * 0.5 + 4}" fill="#a0a0b0" font-size="10" text-anchor="end">₹${(maxVal/2000).toFixed(0)}k</text>
                <text x="${paddingLeft - 10}" y="${paddingTop + chartHeight + 4}" fill="#a0a0b0" font-size="10" text-anchor="end">₹0</text>
            `;
            
            for (let i = 0; i < steps; i++) {
                const x = paddingLeft + (i / (steps - 1)) * chartWidth;
                svg += `<text x="${x}" y="${paddingTop + chartHeight + 20}" fill="#a0a0b0" font-size="10" text-anchor="middle">${revenueData.labels[i]}</text>`;
            }
            
            const pathAreaCol = `M${paddingLeft},${paddingTop + chartHeight} L` + pointsCollected.join(' L') + ` L${width - paddingRight},${paddingTop + chartHeight} Z`;
            const pathAreaInv = `M${paddingLeft},${paddingTop + chartHeight} L` + pointsInvoiced.join(' L') + ` L${width - paddingRight},${paddingTop + chartHeight} Z`;
            
            svg += `
                <defs>
                    <linearGradient id="gradCol" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#10b981" stop-opacity="0.2"/>
                        <stop offset="100%" stop-color="#10b981" stop-opacity="0.0"/>
                    </linearGradient>
                    <linearGradient id="gradInv" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#6366f1" stop-opacity="0.2"/>
                        <stop offset="100%" stop-color="#6366f1" stop-opacity="0.0"/>
                    </linearGradient>
                </defs>
                
                <path d="${pathAreaCol}" fill="url(#gradCol)" />
                <path d="${pathAreaInv}" fill="url(#gradInv)" />
                
                <path d="M${pointsCollected.join(' L')}" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" />
                <path d="M${pointsInvoiced.join(' L')}" fill="none" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round" />
            `;
            
            for (let i = 0; i < steps; i++) {
                const ptC = pointsCollected[i].split(',');
                const ptI = pointsInvoiced[i].split(',');
                
                svg += `
                    <circle cx="${ptC[0]}" cy="${ptC[1]}" r="4" fill="#10b981" stroke="#121216" stroke-width="1.5">
                        <title>Collected: ₹${revenueData.collected[i]}</title>
                    </circle>
                    <circle cx="${ptI[0]}" cy="${ptI[1]}" r="4" fill="#6366f1" stroke="#121216" stroke-width="1.5">
                        <title>Invoiced: ₹${revenueData.invoiced[i]}</title>
                    </circle>
                `;
            }
            
            svg += `</svg>`;
            container.innerHTML = svg;
        },

        renderOfflineClientsSVG: function(clientCtx, clientData) {
            const container = clientCtx.parentElement;
            container.innerHTML = '';
            
            const width = container.clientWidth > 0 ? container.clientWidth : 500;
            const height = container.clientHeight > 0 ? container.clientHeight : 250;
            const paddingLeft = 40;
            const paddingRight = 20;
            const paddingTop = 30;
            const paddingBottom = 40;
            
            const chartWidth = width - paddingLeft - paddingRight;
            const chartHeight = height - paddingTop - paddingBottom;
            
            const maxVal = Math.max(...clientData.counts, 5);
            const steps = clientData.labels.length;
            const barWidth = (chartWidth / steps) * 0.6;
            const barSpacing = (chartWidth / steps) * 0.4;
            
            let svg = `<svg width="100%" height="100%" viewBox="0 0 ${width} ${height}">
                <line x1="${paddingLeft}" y1="${paddingTop}" x2="${width - paddingRight}" y2="${paddingTop}" stroke="rgba(255,255,255,0.05)" />
                <line x1="${paddingLeft}" y1="${paddingTop + chartHeight}" x2="${width - paddingRight}" y2="${paddingTop + chartHeight}" stroke="rgba(255,255,255,0.1)" />
                
                <text x="${paddingLeft - 10}" y="${paddingTop + 4}" fill="#a0a0b0" font-size="10" text-anchor="end">${maxVal}</text>
                <text x="${paddingLeft - 10}" y="${paddingTop + chartHeight + 4}" fill="#a0a0b0" font-size="10" text-anchor="end">0</text>
            `;
            
            for (let i = 0; i < steps; i++) {
                const barHeight = (clientData.counts[i] / maxVal) * chartHeight;
                const x = paddingLeft + i * (barWidth + barSpacing) + barSpacing / 2;
                const y = paddingTop + chartHeight - barHeight;
                
                svg += `
                    <rect x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" rx="4" ry="4" fill="#a855f7">
                        <title>Joined: ${clientData.counts[i]}</title>
                    </rect>
                    <text x="${x + barWidth / 2}" y="${paddingTop + chartHeight + 20}" fill="#a0a0b0" font-size="10" text-anchor="middle">${clientData.labels[i]}</text>
                `;
            }
            
            svg += `</svg>`;
            container.innerHTML = svg;
        },

        initCalendar: function(events) {
            const calendarEl = document.getElementById('calendar-widget-container');
            if (!calendarEl) return;

            let currentDate = new Date();
            
            function renderCalendar(date) {
                calendarEl.innerHTML = '';
                
                const year = date.getFullYear();
                const month = date.getMonth();
                
                const firstDayIndex = new Date(year, month, 1).getDay();
                const lastDay = new Date(year, month + 1, 0).getDate();
                const prevLastDay = new Date(year, month, 0).getDate();
                
                const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                
                // Header
                const header = document.createElement('div');
                header.className = 'calendar-header';
                header.innerHTML = `
                    <button class="calendar-nav-btn" id="cal-prev"><i data-lucide="chevron-left" style="width:16px;height:16px;"></i></button>
                    <div class="calendar-title">${monthNames[month]} ${year}</div>
                    <button class="calendar-nav-btn" id="cal-next"><i data-lucide="chevron-right" style="width:16px;height:16px;"></i></button>
                `;
                calendarEl.appendChild(header);
                
                // Grid
                const grid = document.createElement('div');
                grid.className = 'calendar-grid';
                
                // Day Labels
                const dayLabels = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
                dayLabels.forEach(d => {
                    const label = document.createElement('div');
                    label.className = 'calendar-day-label';
                    label.textContent = d;
                    grid.appendChild(label);
                });
                
                // Prev Month Days
                for (let x = firstDayIndex; x > 0; x--) {
                    const cell = document.createElement('div');
                    cell.className = 'calendar-day-cell other-month';
                    cell.textContent = prevLastDay - x + 1;
                    grid.appendChild(cell);
                }
                
                // Current Month Days
                const today = new Date();
                for (let i = 1; i <= lastDay; i++) {
                    const cell = document.createElement('div');
                    cell.className = 'calendar-day-cell';
                    cell.textContent = i;
                    
                    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                    
                    if (today.getDate() === i && today.getMonth() === month && today.getFullYear() === year) {
                        cell.classList.add('current-day');
                    }
                    
                    // Filter events for this date
                    const dayEvents = events.filter(e => e.date === dateStr);
                    if (dayEvents.length > 0) {
                        const markersContainer = document.createElement('div');
                        markersContainer.className = 'calendar-event-markers';
                        
                        let titles = [];
                        dayEvents.forEach(e => {
                            const marker = document.createElement('div');
                            marker.className = `calendar-marker marker-${e.type}`;
                            markersContainer.appendChild(marker);
                            titles.push(`[${e.type.toUpperCase()}] ${e.title}`);
                        });
                        
                        cell.appendChild(markersContainer);
                        cell.title = titles.join('\n');
                        cell.style.cursor = 'pointer';
                        cell.addEventListener('click', () => {
                            alert(`Events on ${dateStr}:\n\n` + titles.join('\n'));
                        });
                    }
                    
                    grid.appendChild(cell);
                }
                
                // Next Month Days
                const totalCells = firstDayIndex + lastDay;
                const nextMonthCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
                for (let j = 1; j <= nextMonthCells; j++) {
                    const cell = document.createElement('div');
                    cell.className = 'calendar-day-cell other-month';
                    cell.textContent = j;
                    grid.appendChild(cell);
                }
                
                calendarEl.appendChild(grid);
                if (typeof lucide !== 'undefined') lucide.createIcons();
                
                document.getElementById('cal-prev').addEventListener('click', () => {
                    currentDate.setMonth(currentDate.getMonth() - 1);
                    renderCalendar(currentDate);
                });
                document.getElementById('cal-next').addEventListener('click', () => {
                    currentDate.setMonth(currentDate.getMonth() + 1);
                    renderCalendar(currentDate);
                });
            }
            
            renderCalendar(currentDate);
        }
    };

    window.switchCrmTab = function(tabId) {
        document.querySelectorAll('.crm-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('.crm-tab-content').forEach(content => {
            content.classList.remove('active');
        });

        const btn = document.querySelector(`[data-tab="${tabId}"]`);
        if (btn) btn.classList.add('active');
        const content = document.getElementById(tabId);
        if (content) content.classList.add('active');
    };

    // --- Phase 11 UI IMPROVEMENTS LOGIC ---

    // 1. Light/Dark Theme Switcher
    const themeBtn = document.getElementById('theme-toggle-btn');
    const sunIcon = document.getElementById('theme-sun-icon');
    const moonIcon = document.getElementById('theme-moon-icon');

    // Retrieve active theme
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'light') {
        document.body.classList.add('light-mode');
        if (sunIcon) sunIcon.style.display = 'none';
        if (moonIcon) moonIcon.style.display = 'block';
    } else {
        document.body.classList.remove('light-mode');
        if (sunIcon) sunIcon.style.display = 'block';
        if (moonIcon) moonIcon.style.display = 'none';
    }

    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            document.body.classList.toggle('light-mode');
            const isLight = document.body.classList.contains('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');

            if (sunIcon && moonIcon) {
                if (isLight) {
                    sunIcon.style.display = 'none';
                    moonIcon.style.display = 'block';
                } else {
                    sunIcon.style.display = 'block';
                    moonIcon.style.display = 'none';
                }
            }
        });
    }

    // 2. Sidebar Collapse Toggler
    const sidebar = document.getElementById('app-sidebar');
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');

    // Retrieve collapse setting
    const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
    if (isCollapsed && sidebar) {
        sidebar.classList.add('collapsed');
    }

    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
        });
    }

    // 3. Toast Notifications Engine
    window.showToast = function(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast-msg ${type}`;
        toast.innerHTML = `
            <span style="display:flex; align-items:center; gap:0.5rem;">
                <i data-lucide="${type === 'success' ? 'check-circle' : (type === 'danger' ? 'alert-octagon' : 'alert-circle')}" style="width:16px; height:16px;"></i>
                ${message}
            </span>
            <button onclick="this.parentNode.remove()" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; font-size:1.2rem; line-height:1; font-weight:bold;">&times;</button>
        `;
        container.appendChild(toast);
        if (typeof lucide !== 'undefined') lucide.createIcons();

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    };

    // 4. Ctrl+K Inline Search Handler
    window.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            const input = document.getElementById('inline-search-input');
            if (input) input.focus();
        }
    });

    const searchInput = document.getElementById('inline-search-input');
    const searchResults = document.getElementById('inline-search-results');
    if (searchInput && searchResults) {
        searchInput.addEventListener('focus', () => {
            searchInput.style.width = '220px';
            const val = searchInput.value.trim();
            if (val.length >= 2) {
                searchResults.style.display = 'block';
            }
        });

        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
                searchInput.style.width = '150px';
            }
        });

        searchInput.addEventListener('input', debounce(function() {
            const val = searchInput.value.trim();
            if (val.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchResults.innerHTML = '<div style="padding:1rem; text-align:center;"><div class="skeleton" style="width:80%; margin:0.5rem auto;"></div><div class="skeleton" style="width:60%; margin:0.5rem auto;"></div></div>';
            searchResults.style.display = 'block';

            fetch(`index.php?action=mega_search&q=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        searchResults.innerHTML = '<div style="padding:1rem; text-align:center; color:var(--text-muted); font-size:0.8rem;">No results found.</div>';
                    } else {
                        searchResults.innerHTML = data.map(item => `
                            <a href="${item.url}" style="display:block; padding:0.5rem; border-radius:var(--radius-sm); background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05); margin-bottom:0.25rem;">
                                <div style="font-weight:700; color:#fff; font-size:0.85rem;">${item.title}</div>
                                <div style="font-size:0.7rem; color:var(--text-muted); margin-top:0.15rem;">${item.subtitle}</div>
                            </a>
                        `).join('');
                    }
                });
        }, 300));
    }


    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // --- Phase 12 AI FEATURES LOGIC ---

    // 1. AI Chat Toggler
    window.toggleAIChatWindow = function() {
        const win = document.getElementById('ai-chat-window');
        if (win) {
            win.style.display = win.style.display === 'none' ? 'flex' : 'none';
        }
    };

    // Send chat prompt
    window.sendAIChatPrompt = function() {
        const input = document.getElementById('ai-chat-input');
        if (!input || !input.value.trim()) return;

        const val = input.value.trim();
        input.value = '';

        const container = document.getElementById('ai-chat-messages');

        // Append User bubble
        const userDiv = document.createElement('div');
        userDiv.style.alignSelf = 'flex-end';
        userDiv.style.background = 'rgba(99,102,241,0.2)';
        userDiv.style.padding = '0.5rem';
        userDiv.style.borderRadius = 'var(--radius-sm)';
        userDiv.style.color = '#fff';
        userDiv.textContent = val;
        container.appendChild(userDiv);
        container.scrollTop = container.scrollHeight;

        // Fetch AI Response
        fetch(`index.php?action=ai_query&type=chat&prompt=${encodeURIComponent(val)}`)
            .then(res => res.json())
            .then(data => {
                const aiDiv = document.createElement('div');
                aiDiv.style.alignSelf = 'flex-start';
                aiDiv.style.background = 'rgba(255,255,255,0.03)';
                aiDiv.style.padding = '0.5rem';
                aiDiv.style.borderRadius = 'var(--radius-sm)';
                aiDiv.style.color = 'var(--text-muted)';
                aiDiv.innerHTML = data.response.replace(/\n/g, '<br>');
                container.appendChild(aiDiv);
                container.scrollTop = container.scrollHeight;
            });
    };

    // 2. AI Task Suggest Subtasks
    window.suggestAISubtasks = function() {
        const titleInput = document.getElementById('t-title');
        const descArea = document.getElementById('t-desc');
        if (!titleInput || !descArea) return;

        const title = titleInput.value.trim() || 'General compliance task';
        App.toast("Generating subtask checklists...", "warning");

        fetch(`index.php?action=ai_query&type=subtasks&title=${encodeURIComponent(title)}`)
            .then(res => res.json())
            .then(data => {
                descArea.value = (descArea.value ? descArea.value + "\n" : "") + data.subtasks;
                App.toast("AI Checklist suggested successfully!");
            });
    };

    // 3. AI Email Draft Generator
    window.generateAIEmailDraft = function(templateName) {
        const bodyArea = document.getElementById('template-body-textarea');
        if (!bodyArea) return;

        App.toast("Generating custom email draft...", "warning");

        fetch(`index.php?action=ai_query&type=email&client_name={client_name}&topic=${encodeURIComponent(templateName)}`)
            .then(res => res.json())
            .then(data => {
                bodyArea.value = data.draft;
                App.toast("AI Email draft loaded!");
            });
    };

    // 4. AI Report Summary Analysis
    window.generateAIReportSummary = function(reportType) {
        // Collect current page tables summary if possible
        const summaryCard = document.querySelector('.card.glass-card');
        let mockReportObj = {
            billed_revenue: 150000.00,
            collected_revenue: 120000.00,
            total_expenses: 85000.00,
            net_profit: 35000.00,
            profit_margin: 23.3
        };

        App.toast("Analyzing operational parameters...", "warning");

        const formData = new FormData();
        formData.append('data', JSON.stringify(mockReportObj));

        fetch(`index.php?action=ai_query&type=report_summary`, {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                alert(data.summary.replace(/###/g, '').replace(/\*\*/g, ''));
            });
    };

    // Auto-initialize dashboard components if data is available
    if (window.DashboardData && window.Dashboard) {
        window.Dashboard.initCharts(window.DashboardData.revenue, window.DashboardData.clients);
        window.Dashboard.initCalendar(window.DashboardData.calendarEvents);
    }
});

