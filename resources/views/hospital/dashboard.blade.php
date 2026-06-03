<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System - RBAC Dashboard</title>
    <!-- Modern Premium Typography (Outfit & Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0b0f19;
            --bg-secondary: #161f30;
            --bg-tertiary: #1f2c45;
            --accent-blue: #3b82f6;
            --accent-teal: #14b8a6;
            --accent-purple: #a855f7;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: #2d3d5a;
            --success-color: #22c55e;
            --danger-color: #ef4444;
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-main);
            font-family: var(--font-body);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }

        /* Premium Sidebar */
        aside {
            width: 280px;
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            padding: 24px;
        }

        .brand {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 800;
            color: transparent;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal));
            -webkit-background-clip: text;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-section {
            margin-bottom: 24px;
        }

        .nav-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--text-main);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .nav-item:hover {
            background-color: var(--bg-tertiary);
            color: var(--accent-blue);
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(20, 184, 166, 0.1));
            border-left: 4px solid var(--accent-teal);
            color: var(--text-main);
        }

        /* Main Content */
        main {
            flex-grow: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            gap: 30px;
            overflow-y: auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background-color: var(--bg-secondary);
            padding: 8px 16px;
            border-radius: 30px;
            border: 1px solid var(--border-color);
        }

        .role-badge {
            background-color: rgba(168, 85, 247, 0.2);
            color: var(--accent-purple);
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            text-transform: uppercase;
        }

        h1, h2, h3 {
            font-family: var(--font-display);
            margin: 0;
        }

        .grid-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .card {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .btn {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-teal));
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-danger {
            background: var(--danger-color);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }

        /* Healthcare Masking Alert */
        .mask-alert {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px dashed var(--danger-color);
            color: #fca5a5;
            padding: 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

    <!-- Premium Navigation Sidebar -->
    <aside>
        <div class="brand">
            <span>🏥</span> Hospital RBAC
        </div>

        <div class="nav-section">
            <div class="nav-title">Clinical Operations</div>
            
            @can('view patients')
                <a href="#" class="nav-item active">Patients</a>
            @endcan

            @can('view appointments')
                <a href="#" class="nav-item">Appointments</a>
            @endcan

            @can('view prescriptions')
                <a href="#" class="nav-item">Pharmacy / Prescriptions</a>
            @endcan

            @can('view lab requests')
                <a href="#" class="nav-item">Lab / Diagnostics</a>
            @endcan
        </div>

        @can('view invoice')
        <div class="nav-section">
            <div class="nav-title">Financials</div>
            <a href="#" class="nav-item">Billing & Invoices</a>
        </div>
        @endcan

        <!-- Super Administration Controls -->
        @role('super-admin|hospital-admin')
        <div class="nav-section">
            <div class="nav-title">System Settings</div>
            <a href="#" class="nav-item">User Management</a>
            <a href="#" class="nav-item">Audit Logging</a>
            <a href="#" class="nav-item">System Settings</a>
        </div>
        @endrole
    </aside>

    <!-- Main Workspace -->
    <main>
        <header>
            <div>
                <h1>Healthcare RBAC Demonstration</h1>
                <p style="color: var(--text-muted); margin-top: 4px;">Clean blade-level authorization using Spatie directives</p>
            </div>
            <div class="user-profile">
                <span>{{ auth()->user()->name ?? 'Demonstration User' }}</span>
                <span class="role-badge">{{ auth()->user()->roles->first()->name ?? 'No Role' }}</span>
            </div>
        </header>

        <section class="grid-dashboard">
            <!-- Patient Records Panel -->
            <div class="card">
                <div class="card-header">
                    <h3>Patient Records</h3>
                    @can('create patients')
                        <button class="btn">+ Register Patient</button>
                    @endcan
                </div>
                <p style="color: var(--text-muted); font-size: 14px;">Logged-in user-specific patient records overview:</p>
                
                <!-- Mock Patient Display with Conditional Field Masking -->
                <div style="background-color: var(--bg-tertiary); padding: 16px; border-radius: 12px; margin-top: 15px;">
                    <strong style="display: block;">John Doe (Patient ID: #8291)</strong>
                    <span style="font-size: 12px; color: var(--text-muted);">Phone: 555-0199</span>
                    
                    <div style="margin-top: 12px; border-top: 1px solid var(--border-color); padding-top: 12px;">
                        <strong>Medical History:</strong>
                        
                        @can('view patient medical history', $patient ?? null)
                            <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--accent-teal);">
                                Patient diagnosed with Hypertension. Undergoing Beta-Blockers course.
                            </p>
                        @else
                            <div class="mask-alert">
                                <span>🔒</span>
                                <div>
                                    <strong>Access Denied</strong>: Your current role is not authorized to view John Doe's clinical/medical history.
                                </div>
                            </div>
                        @endcan
                    </div>
                </div>

                <div style="margin-top: 16px; display: flex; gap: 8px;">
                    @can('edit patients')
                        <button class="btn btn-secondary">Edit Demographics</button>
                    @endcan
                    @can('delete patients')
                        <button class="btn btn-danger">Purge Patient</button>
                    @endcan
                </div>
            </div>

            <!-- Lab Results Panel -->
            <div class="card">
                <div class="card-header">
                    <h3>Lab & Diagnostics</h3>
                    @can('create lab report')
                        <button class="btn">New Request</button>
                    @endcan
                </div>
                <p style="color: var(--text-muted); font-size: 14px;">Lab reports and analytical records:</p>

                <div style="background-color: var(--bg-tertiary); padding: 16px; border-radius: 12px; margin-top: 15px;">
                    <strong>Blood Panel Test (CBC)</strong>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                        <span style="font-size: 12px; background: rgba(59, 130, 246, 0.2); color: var(--accent-blue); padding: 2px 6px; border-radius: 4px;">
                            Status: Pending Review
                        </span>
                        
                        @can('approve lab report', $lab_report ?? null)
                            <button class="btn" style="padding: 4px 8px; font-size: 11px;">Approve Report</button>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Financials / Billing Panel -->
            <div class="card">
                <div class="card-header">
                    <h3>Billing & Financials</h3>
                    @can('create invoice')
                        <button class="btn">+ Issue Invoice</button>
                    @endcan
                </div>
                <p style="color: var(--text-muted); font-size: 14px;">Invoices and balance tracking:</p>

                <div style="background-color: var(--bg-tertiary); padding: 16px; border-radius: 12px; margin-top: 15px;">
                    <div style="display: flex; justify-content: space-between;">
                        <strong>Invoice #INV-2901</strong>
                        <strong style="color: var(--accent-teal);">$450.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                        <span style="font-size: 12px; color: var(--text-muted);">Patient: John Doe</span>
                        @can('receive payment', $invoice ?? null)
                            <button class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;">Process Payment</button>
                        @endcan
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
