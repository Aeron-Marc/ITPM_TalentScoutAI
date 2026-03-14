# TalentScout AI - Role-Based UI Structure

## Organized by User Roles

The platform is now separated into distinct role-based sections with dedicated UI for each user type.

---

## **Directory Structure**

```
ITPM_TalentScout/
├── index.html                          # Main landing page (all users)
│
├── employees/                          # JOB SEEKERS AREA
│   ├── index.html                      # Job seeker role portal
│   └── modules/
│       └── index.html                  # Job seeker tools dashboard
│           ├── Job Postings
│           ├── AI Job Matching
│           ├── Skill Gap Analysis
│           ├── Application Tracker
│           ├── Blind Hiring Info
│           └── Resume Builder (coming soon)
│
├── employers/                          # EMPLOYERS AREA
│   ├── index.html                      # Employer role portal
│   └── modules/
│       └── index.html                  # Employer tools dashboard
│           ├── Post Job Listings
│           ├── Employee Finder
│           ├── Blind Hiring
│           ├── Application Tracker
│           ├── Company Registration
│           └── Chat & SMS
│
├── peso-dashboard/                     # PESO ADMIN AREA
│   ├── index.html                      # Admin role portal & dashboard
│
├── job-postings/                       # SHARED MODULES (accessible from relevant roles)
├── ai-matching/
├── blind-hiring/
├── applicant-tracking/
├── skill-gap-analysis/
│
└── styles/
    ├── global.css                      # Global theme & components
    └── page-layout.css                 # Shared page layouts
```

---

## **Role Portals (Entry Points)**

### **1. Job Seekers** → `employees/`
- **Portal**: `/employees/index.html`
- **Tools Dashboard**: `/employees/modules/index.html`
- **Available Tools**:
  - 📋 Job Postings - Browse opportunities
  - 🤖 AI Job Matching - Get recommendations
  - 📊 Skill Gap Analysis - Identify growth areas
  - 📑 Application Tracker - Monitor applications
  - 🫥 Blind Hiring Info - Learn about fair screening
  - ✏️ Resume Builder (coming soon)

### **2. Employers** → `employers/`
- **Portal**: `/employers/index.html`
- **Tools Dashboard**: `/employers/modules/index.html`
- **Available Tools**:
  - 📝 Post Job Listings - Create job postings
  - 🔍 Employee Finder - Search talent pool
  - 🫥 Blind Hiring - Fair candidate evaluation
  - 📊 Application Tracker - Manage hiring pipeline
  - 📋 Company Registration - Verify business
  - 💬 Chat & SMS - Direct communication

### **3. PESO Admin** → `peso-dashboard/`
- **Portal**: `/peso-dashboard/index.html`
- **Available Tools**:
  - 🖥️ Dashboard - Metrics & analytics
  - 👥 Employer Overview - Manage employers
  - 👤 Employee Overview - Manage job seekers
  - 📊 Application Tracker - Monitor all applications

---

## **Navigation Structure**

### **Main Landing Page** (`index.html`)
Shows all three role options with descriptions. Users click to enter their role portal.

```
Home → Choose Role
  ├── For Job Seekers → employees/index.html
  ├── For Employers → employers/index.html
  └── PESO Admin → peso-dashboard/index.html
```

### **Role Portal Navigation**
Each role has a dedicated portal with:
- Hero section explaining role benefits
- Feature highlights
- "View All Tools" button → Links to `/modules/index.html`

### **Modules Dashboard Navigation**
Shows all available tools for that role:
- Grid of module cards
- Role-specific descriptions
- Quick links to each module

### **Individual Modules**
Each module (job-postings, ai-matching, etc.) is now:
- Accessible from role-specific modules dashboard
- Shows breadcrumb or back link to modules dashboard
- Maintains shared navigation across all pages

---

## **User Flow by Role**

### **Job Seeker Flow**
```
Landing Page (index.html)
  ↓
"For Job Seekers" Button
  ↓
Job Seeker Portal (employees/index.html)
  ↓
"View All Tools" Button
  ↓
Tools Dashboard (employees/modules/index.html)
  ↓
Select Module (job-postings, ai-matching, etc.)
  ↓
Use Module
```

### **Employer Flow**
```
Landing Page (index.html)
  ↓
"For Employers" Button
  ↓
Employer Portal (employers/index.html)
  ↓
"View All Tools" Button
  ↓
Tools Dashboard (employers/modules/index.html)
  ↓
Select Module (job-postings, employee-finder, etc.)
  ↓
Use Module
```

### **Admin Flow**
```
Landing Page (index.html)
  ↓
"PESO Admin" Button
  ↓
Admin Dashboard (peso-dashboard/index.html)
  ↓
View Analytics & Metrics
  ↓
Manage Employers/Employees
```

---

## **Role-Specific Module Access**

### **Job Seekers Can Access** ✓
- Job Postings
- AI Matching
- Skill Gap Analysis  
- Application Tracker
- Blind Hiring (Info only)
- Resume Builder (future)

### **Employers Can Access** ✓
- Job Postings (create/manage)
- Employee Finder
- Blind Hiring (create blind profiles)
- Application Tracker
- Company Registration
- Chat & SMS

### **PESO Admin Can Access** ✓
- Full Dashboard & Metrics
- Employer Management
- Job Seeker Management
- Application Tracker (all)
- System analytics

---

## **Shared Modules**

These modules are shared but appear differently based on context:

| Module | Role 1 | Role 2 | Role 3 |
|--------|--------|--------|--------|
| **applicant-tracking** | Candidate view | Employer view | Admin view |
| **ai-matching** | For job seekers | For finding talent | Dashboard metric |
| **blind-hiring** | Learn about | Use for screening | Monitor |
| **job-postings** | Browse jobs | Created/managed | Overview |
| **skill-gap-analysis** | Analysis tool | Not directly used | Metrics |

---

## **Benefits of Role-Based Separation**

✓ **Clear Navigation** - Users know exactly where they fit  
✓ **Role-Specific UI** - See only relevant tools and features  
✓ **Better UX Flow** - Guided journey per role  
✓ **Reduced Confusion** - Less overwhelming interface  
✓ **Security-Ready** - Easier to implement role-based access control  
✓ **Easy to Extend** - Add new roles/modules without affecting others  

---

## **Next Steps (With Login)**

When login is added:
1. Detect user role from login credentials
2. Redirect to appropriate portal (employees, employers, or peso-dashboard)
3. Show only role-specific modules in modules dashboard
4. Implement role-based permissions for shared modules
5. Track user activity per role

For now: **UI is separated by role, ready for login implementation** ✓
