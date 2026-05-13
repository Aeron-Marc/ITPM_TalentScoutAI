<?php
/**
 * Skill Normalization Service
 * Maps skill variations to canonical names across ALL industries
 * Supports expansion while preventing redundancy
 */

class SkillNormalizer {
    private $normalizations = [];
    
    public function __construct() {
        $this->normalizations = $this->buildSkillMap();
    }
    
    /**
     * Build comprehensive skill mapping covering all industries
     * Maps variations → canonical skill name
     */
    private function buildSkillMap() {
        return [
            // ============ TECHNOLOGY & IT ============
            // JavaScript Ecosystem
            'js' => 'JavaScript',
            'javascript' => 'JavaScript',
            'node' => 'Node.js',
            'node.js' => 'Node.js',
            'nodejs' => 'Node.js',
            'ts' => 'TypeScript',
            'typescript' => 'TypeScript',
            
            // Frontend Frameworks
            'react' => 'React',
            'react.js' => 'React',
            'reactjs' => 'React',
            'vue' => 'Vue.js',
            'vue.js' => 'Vue.js',
            'vuejs' => 'Vue.js',
            'angular' => 'Angular',
            'angular.js' => 'Angular',
            'angularjs' => 'Angular',
            'svelte' => 'Svelte',
            'next' => 'Next.js',
            'next.js' => 'Next.js',
            'nextjs' => 'Next.js',
            'nuxt' => 'Nuxt.js',
            'nuxt.js' => 'Nuxt.js',
            'nuxtjs' => 'Nuxt.js',
            
            // Styling
            'css' => 'CSS',
            'css2' => 'CSS',
            'css3' => 'CSS',
            'scss' => 'SCSS',
            'sass' => 'SCSS',
            'less' => 'Less',
            'bootstrap' => 'Bootstrap',
            'tailwind' => 'Tailwind CSS',
            'tailwind css' => 'Tailwind CSS',
            'tailwindcss' => 'Tailwind CSS',
            
            // Markup & Data
            'html' => 'HTML',
            'html5' => 'HTML',
            'html 5' => 'HTML',
            'xml' => 'XML',
            'json' => 'JSON',
            'yaml' => 'YAML',
            'yml' => 'YAML',
            'toml' => 'TOML',
            
            // Backend Languages
            'php' => 'PHP',
            'php7' => 'PHP',
            'php8' => 'PHP',
            'python' => 'Python',
            'py' => 'Python',
            'java' => 'Java',
            'c#' => 'C#',
            'csharp' => 'C#',
            'c#.net' => 'C#',
            'dotnet' => '.NET',
            '.net' => '.NET',
            'net' => '.NET',
            'c++' => 'C++',
            'cpp' => 'C++',
            'c' => 'C',
            'go' => 'Go',
            'golang' => 'Go',
            'rust' => 'Rust',
            'ruby' => 'Ruby',
            'ruby on rails' => 'Ruby on Rails',
            'rails' => 'Ruby on Rails',
            'ror' => 'Ruby on Rails',
            'kotlin' => 'Kotlin',
            'swift' => 'Swift',
            'perl' => 'Perl',
            'scala' => 'Scala',
            'r' => 'R Programming',
            'r programming' => 'R Programming',
            'matlab' => 'MATLAB',
            'r lang' => 'R Programming',
            
            // Databases
            'sql' => 'SQL',
            'mysql' => 'MySQL',
            'postgres' => 'PostgreSQL',
            'postgresql' => 'PostgreSQL',
            'sqlite' => 'SQLite',
            'mongodb' => 'MongoDB',
            'mongo' => 'MongoDB',
            'redis' => 'Redis',
            'firebase' => 'Firebase',
            'dynamodb' => 'DynamoDB',
            'cassandra' => 'Cassandra',
            'elasticsearch' => 'Elasticsearch',
            'mariadb' => 'MariaDB',
            'oracle' => 'Oracle Database',
            'sql server' => 'SQL Server',
            'sqlserver' => 'SQL Server',
            'neo4j' => 'Neo4j',
            'couchdb' => 'CouchDB',
            
            // DevOps & Cloud & Infrastructure
            'docker' => 'Docker',
            'kubernetes' => 'Kubernetes',
            'k8s' => 'Kubernetes',
            'aws' => 'AWS',
            'amazon web services' => 'AWS',
            'gcp' => 'Google Cloud',
            'google cloud' => 'Google Cloud',
            'azure' => 'Azure',
            'microsoft azure' => 'Azure',
            'heroku' => 'Heroku',
            'jenkins' => 'Jenkins',
            'gitlab' => 'GitLab',
            'github' => 'GitHub',
            'git' => 'Git',
            'svn' => 'SVN',
            'terraform' => 'Terraform',
            'ansible' => 'Ansible',
            'circleci' => 'CircleCI',
            'ci/cd' => 'CI/CD',
            'cicd' => 'CI/CD',
            'nginx' => 'Nginx',
            'apache' => 'Apache',
            'iam' => 'IAM',
            'cloudflare' => 'Cloudflare',
            'openstack' => 'OpenStack',
            'linux' => 'Linux',
            'unix' => 'Unix',
            'windows' => 'Windows',
            'macos' => 'macOS',
            'server administration' => 'Server Administration',
            'system administration' => 'System Administration',
            'networking' => 'Networking',
            'network administration' => 'Network Administration',
            'vpn' => 'VPN',
            'firewall' => 'Firewall',
            
            // Frontend Tools
            'webpack' => 'Webpack',
            'vite' => 'Vite',
            'gulp' => 'Gulp',
            'grunt' => 'Grunt',
            'npm' => 'NPM',
            'yarn' => 'Yarn',
            'pnpm' => 'pnpm',
            'bower' => 'Bower',
            
            // Testing & QA
            'jest' => 'Jest',
            'mocha' => 'Mocha',
            'jasmine' => 'Jasmine',
            'rspec' => 'RSpec',
            'pytest' => 'PyTest',
            'unittest' => 'UnitTest',
            'selenium' => 'Selenium',
            'cypress' => 'Cypress',
            'playwright' => 'Playwright',
            'appium' => 'Appium',
            'postman' => 'Postman',
            'jmeter' => 'JMeter',
            'load testing' => 'Load Testing',
            'performance testing' => 'Performance Testing',
            'qa' => 'QA Testing',
            'quality assurance' => 'QA Testing',
            'test automation' => 'Test Automation',
            'manual testing' => 'Manual Testing',
            
            // APIs & Real-time & Integration
            'rest' => 'REST',
            'restful' => 'REST',
            'graphql' => 'GraphQL',
            'websocket' => 'WebSocket',
            'websockets' => 'WebSocket',
            'socket.io' => 'Socket.io',
            'socketio' => 'Socket.io',
            'soap' => 'SOAP',
            'api development' => 'API Development',
            'api design' => 'API Design',
            'integration' => 'Integration',
            'etl' => 'ETL',
            
            // Mobile & Cross-platform
            'react native' => 'React Native',
            'react-native' => 'React Native',
            'reactnative' => 'React Native',
            'flutter' => 'Flutter',
            'ios' => 'iOS',
            'android' => 'Android',
            'kotlin' => 'Kotlin',
            'swift' => 'Swift',
            'xamarin' => 'Xamarin',
            'cordova' => 'Cordova',
            'ionic' => 'Ionic',
            'nativescript' => 'NativeScript',
            'mobile development' => 'Mobile Development',
            
            // Design & UI/UX
            'figma' => 'Figma',
            'sketch' => 'Sketch',
            'adobe xd' => 'Adobe XD',
            'adobexd' => 'Adobe XD',
            'photoshop' => 'Photoshop',
            'illustrator' => 'Illustrator',
            'indesign' => 'InDesign',
            'ui' => 'UI Design',
            'ux' => 'UX Design',
            'ui/ux' => 'UI/UX Design',
            'web design' => 'Web Design',
            'graphic design' => 'Graphic Design',
            'interaction design' => 'Interaction Design',
            'wireframing' => 'Wireframing',
            'prototyping' => 'Prototyping',
            'design thinking' => 'Design Thinking',
            'usability testing' => 'Usability Testing',
            'information architecture' => 'Information Architecture',
            'responsive design' => 'Responsive Design',
            'accessibility' => 'Accessibility (A11y)',
            'a11y' => 'Accessibility (A11y)',
            
            // Data & Analytics
            'data analysis' => 'Data Analysis',
            'analytics' => 'Analytics',
            'business intelligence' => 'Business Intelligence',
            'bi' => 'Business Intelligence',
            'tableau' => 'Tableau',
            'power bi' => 'Power BI',
            'powerbi' => 'Power BI',
            'excel' => 'Excel',
            'spreadsheets' => 'Spreadsheets',
            'google sheets' => 'Google Sheets',
            'data visualization' => 'Data Visualization',
            'dashboards' => 'Dashboards',
            'big data' => 'Big Data',
            'hadoop' => 'Hadoop',
            'spark' => 'Apache Spark',
            'data engineering' => 'Data Engineering',
            'etl' => 'ETL',
            'data pipeline' => 'Data Pipeline',
            'data warehousing' => 'Data Warehousing',
            'statistical analysis' => 'Statistical Analysis',
            'predictive modeling' => 'Predictive Modeling',
            
            // AI & Machine Learning
            'machine learning' => 'Machine Learning',
            'ml' => 'Machine Learning',
            'artificial intelligence' => 'Artificial Intelligence',
            'ai' => 'Artificial Intelligence',
            'deep learning' => 'Deep Learning',
            'neural networks' => 'Neural Networks',
            'nlp' => 'Natural Language Processing',
            'natural language processing' => 'Natural Language Processing',
            'computer vision' => 'Computer Vision',
            'cv' => 'Computer Vision',
            'tensorflow' => 'TensorFlow',
            'pytorch' => 'PyTorch',
            'scikit-learn' => 'Scikit-learn',
            'keras' => 'Keras',
            'pandas' => 'Pandas',
            'numpy' => 'NumPy',
            'llm' => 'Large Language Models',
            'generative ai' => 'Generative AI',
            'prompt engineering' => 'Prompt Engineering',
            
            // Security
            'cybersecurity' => 'Cybersecurity',
            'security' => 'Cybersecurity',
            'penetration testing' => 'Penetration Testing',
            'ethical hacking' => 'Ethical Hacking',
            'ssl/tls' => 'SSL/TLS',
            'encryption' => 'Encryption',
            'oauth' => 'OAuth',
            'jwt' => 'JWT',
            'authentication' => 'Authentication',
            'authorization' => 'Authorization',
            'infosec' => 'Information Security',
            'compliance' => 'Compliance',
            'gdpr' => 'GDPR',
            'iso 27001' => 'ISO 27001',
            
            // Documentation & Communication
            'markdown' => 'Markdown',
            'latex' => 'LaTeX',
            'documentation' => 'Technical Documentation',
            'technical writing' => 'Technical Writing',
            'technical documentation' => 'Technical Documentation',
            'api documentation' => 'API Documentation',
            'seo' => 'SEO',
            'copywriting' => 'Copywriting',
            'content writing' => 'Content Writing',
            
            // Other IT Skills
            'microservices' => 'Microservices',
            'serverless' => 'Serverless Architecture',
            'rabbitmq' => 'RabbitMQ',
            'kafka' => 'Apache Kafka',
            'message queue' => 'Message Queuing',
            'event-driven' => 'Event-Driven Architecture',
            'cqrs' => 'CQRS',
            'monolithic' => 'Monolithic Architecture',
            'distributed systems' => 'Distributed Systems',
            'caching' => 'Caching',
            'cdn' => 'CDN',
            'performance optimization' => 'Performance Optimization',
            'scalability' => 'Scalability',
            
            // ============ BUSINESS & MANAGEMENT ============
            'project management' => 'Project Management',
            'agile' => 'Agile',
            'scrum' => 'Scrum',
            'kanban' => 'Kanban',
            'waterfall' => 'Waterfall',
            'jira' => 'Jira',
            'confluence' => 'Confluence',
            'asana' => 'Asana',
            'monday.com' => 'Monday.com',
            'trello' => 'Trello',
            'notion' => 'Notion',
            'leadership' => 'Leadership',
            'management' => 'Management',
            'people management' => 'People Management',
            'team management' => 'Team Management',
            'stakeholder management' => 'Stakeholder Management',
            'strategic planning' => 'Strategic Planning',
            'business analysis' => 'Business Analysis',
            'requirements gathering' => 'Requirements Gathering',
            'process improvement' => 'Process Improvement',
            'change management' => 'Change Management',
            'communication' => 'Communication',
            'presentation skills' => 'Presentation Skills',
            'public speaking' => 'Public Speaking',
            'negotiation' => 'Negotiation',
            'conflict resolution' => 'Conflict Resolution',
            'problem solving' => 'Problem Solving',
            'critical thinking' => 'Critical Thinking',
            'decision making' => 'Decision Making',
            'teamwork' => 'Teamwork',
            'collaboration' => 'Collaboration',
            'cross-functional collaboration' => 'Cross-Functional Collaboration',
            'mentoring' => 'Mentoring',
            'coaching' => 'Coaching',
            'training' => 'Training & Development',
            'employee engagement' => 'Employee Engagement',
            'performance management' => 'Performance Management',
            'budget management' => 'Budget Management',
            'financial management' => 'Financial Management',
            'roi' => 'ROI Analysis',
            'forecasting' => 'Forecasting',
            'market analysis' => 'Market Analysis',
            'competitive analysis' => 'Competitive Analysis',
            'business development' => 'Business Development',
            'sales' => 'Sales',
            'customer relations' => 'Customer Relations',
            'relationship building' => 'Relationship Building',
            
            // ============ FINANCE & ACCOUNTING ============
            'accounting' => 'Accounting',
            'bookkeeping' => 'Bookkeeping',
            'tax' => 'Tax Accounting',
            'tax accounting' => 'Tax Accounting',
            'financial analysis' => 'Financial Analysis',
            'financial planning' => 'Financial Planning',
            'budgeting' => 'Budgeting',
            'forecasting' => 'Forecasting',
            'gaap' => 'GAAP',
            'ifrs' => 'IFRS',
            'audit' => 'Auditing',
            'internal audit' => 'Internal Audit',
            'financial reporting' => 'Financial Reporting',
            'taxation' => 'Taxation',
            'payroll' => 'Payroll',
            'quickbooks' => 'QuickBooks',
            'xero' => 'Xero',
            'sap' => 'SAP',
            'oracle financials' => 'Oracle Financials',
            'treasury' => 'Treasury',
            'credit analysis' => 'Credit Analysis',
            'risk management' => 'Risk Management',
            'compliance' => 'Compliance',
            'regulatory compliance' => 'Regulatory Compliance',
            'investment analysis' => 'Investment Analysis',
            'portfolio management' => 'Portfolio Management',
            'forex' => 'Forex Trading',
            'stock market' => 'Stock Market',
            'derivatives' => 'Derivatives',
            'fixed income' => 'Fixed Income',
            
            // ============ HEALTHCARE & MEDICAL ============
            'nursing' => 'Nursing',
            'patient care' => 'Patient Care',
            'clinical skills' => 'Clinical Skills',
            'medical terminology' => 'Medical Terminology',
            'pharmacology' => 'Pharmacology',
            'anatomy' => 'Anatomy',
            'physiology' => 'Physiology',
            'medical coding' => 'Medical Coding',
            'icd-10' => 'ICD-10',
            'cpt' => 'CPT Coding',
            'emd' => 'EMR/EHR',
            'emr' => 'EMR/EHR',
            'ehr' => 'EMR/EHR',
            'healthcare administration' => 'Healthcare Administration',
            'health information management' => 'Health Information Management',
            'medical records' => 'Medical Records',
            'hipaa' => 'HIPAA',
            'patient communication' => 'Patient Communication',
            'telemedicine' => 'Telemedicine',
            'healthcare compliance' => 'Healthcare Compliance',
            'insurance billing' => 'Insurance Billing',
            'medical billing' => 'Medical Billing',
            'surgery' => 'Surgery',
            'anesthesia' => 'Anesthesia',
            'radiology' => 'Radiology',
            'pathology' => 'Pathology',
            'laboratory' => 'Laboratory Work',
            'lab work' => 'Laboratory Work',
            'dialysis' => 'Dialysis',
            'wound care' => 'Wound Care',
            'phlebotomy' => 'Phlebotomy',
            'health coaching' => 'Health Coaching',
            'nutrition' => 'Nutrition',
            'mental health' => 'Mental Health',
            'psychiatry' => 'Psychiatry',
            'psychology' => 'Psychology',
            'occupational therapy' => 'Occupational Therapy',
            'physical therapy' => 'Physical Therapy',
            'respiratory therapy' => 'Respiratory Therapy',
            'emergency medicine' => 'Emergency Medicine',
            'critical care' => 'Critical Care',
            'geriatrics' => 'Geriatrics',
            'pediatrics' => 'Pediatrics',
            'obstetrics' => 'Obstetrics',
            'dentistry' => 'Dentistry',
            'veterinary' => 'Veterinary Medicine',
            
            // ============ SALES & MARKETING ============
            'sales' => 'Sales',
            'b2b sales' => 'B2B Sales',
            'b2c sales' => 'B2C Sales',
            'sales management' => 'Sales Management',
            'account management' => 'Account Management',
            'customer retention' => 'Customer Retention',
            'customer acquisition' => 'Customer Acquisition',
            'cold calling' => 'Cold Calling',
            'lead generation' => 'Lead Generation',
            'pipeline management' => 'Pipeline Management',
            'crm' => 'CRM',
            'salesforce' => 'Salesforce',
            'hubspot' => 'HubSpot',
            'microsoft dynamics' => 'Microsoft Dynamics',
            'pipedrive' => 'Pipedrive',
            'zoho' => 'Zoho',
            'marketing' => 'Marketing',
            'digital marketing' => 'Digital Marketing',
            'social media marketing' => 'Social Media Marketing',
            'email marketing' => 'Email Marketing',
            'content marketing' => 'Content Marketing',
            'seo' => 'SEO',
            'sem' => 'SEM',
            'ppc' => 'PPC Advertising',
            'google ads' => 'Google Ads',
            'facebook ads' => 'Facebook Ads',
            'linkedin marketing' => 'LinkedIn Marketing',
            'brand management' => 'Brand Management',
            'marketing strategy' => 'Marketing Strategy',
            'market research' => 'Market Research',
            'consumer insights' => 'Consumer Insights',
            'product marketing' => 'Product Marketing',
            'product launch' => 'Product Launch',
            'campaign management' => 'Campaign Management',
            'event marketing' => 'Event Marketing',
            'sponsorship' => 'Sponsorship',
            'public relations' => 'Public Relations',
            'pr' => 'Public Relations',
            'media relations' => 'Media Relations',
            'communications' => 'Communications',
            'advertising' => 'Advertising',
            'creative' => 'Creative Services',
            'copywriting' => 'Copywriting',
            'content creation' => 'Content Creation',
            'video marketing' => 'Video Marketing',
            'influencer marketing' => 'Influencer Marketing',
            'affiliate marketing' => 'Affiliate Marketing',
            'conversion rate optimization' => 'Conversion Rate Optimization',
            'cro' => 'Conversion Rate Optimization',
            'marketing automation' => 'Marketing Automation',
            'hubspot' => 'HubSpot',
            'mailchimp' => 'Mailchimp',
            'marketo' => 'Marketo',
            'adobe marketing cloud' => 'Adobe Marketing Cloud',
            
            // ============ EDUCATION & TRAINING ============
            'teaching' => 'Teaching',
            'curriculum development' => 'Curriculum Development',
            'instructional design' => 'Instructional Design',
            'e-learning' => 'E-Learning Development',
            'online teaching' => 'Online Teaching',
            'distance learning' => 'Distance Learning',
            'student engagement' => 'Student Engagement',
            'assessment' => 'Assessment & Evaluation',
            'grading' => 'Grading & Assessment',
            'learning management systems' => 'Learning Management Systems',
            'lms' => 'Learning Management Systems',
            'moodle' => 'Moodle',
            'blackboard' => 'Blackboard',
            'canvas' => 'Canvas LMS',
            'schoology' => 'Schoology',
            'adult learning' => 'Adult Learning',
            'corporate training' => 'Corporate Training',
            'training delivery' => 'Training Delivery',
            'facilitation' => 'Facilitation',
            'workshop design' => 'Workshop Design',
            'tutoring' => 'Tutoring',
            'coaching' => 'Coaching',
            'mentoring' => 'Mentoring',
            'education administration' => 'Education Administration',
            'school management' => 'School Management',
            'student counseling' => 'Student Counseling',
            'special education' => 'Special Education',
            'early childhood education' => 'Early Childhood Education',
            'higher education' => 'Higher Education',
            'stem education' => 'STEM Education',
            'foreign languages' => 'Foreign Languages',
            'english' => 'English',
            'math' => 'Mathematics',
            'science' => 'Science',
            'literature' => 'Literature',
            'history' => 'History',
            'social studies' => 'Social Studies',
            'arts education' => 'Arts Education',
            'music education' => 'Music Education',
            'physical education' => 'Physical Education',
            'vocational training' => 'Vocational Training',
            
            // ============ HUMAN RESOURCES ============
            'human resources' => 'Human Resources',
            'hr' => 'Human Resources',
            'recruiting' => 'Recruiting',
            'recruitment' => 'Recruitment',
            'talent acquisition' => 'Talent Acquisition',
            'employee relations' => 'Employee Relations',
            'performance management' => 'Performance Management',
            'compensation' => 'Compensation & Benefits',
            'benefits administration' => 'Benefits Administration',
            'payroll' => 'Payroll',
            'hr compliance' => 'HR Compliance',
            'employment law' => 'Employment Law',
            'onboarding' => 'Onboarding',
            'offboarding' => 'Offboarding',
            'training development' => 'Training & Development',
            'succession planning' => 'Succession Planning',
            'organizational development' => 'Organizational Development',
            'culture development' => 'Culture Development',
            'diversity and inclusion' => 'Diversity & Inclusion',
            'di' => 'Diversity & Inclusion',
            'workforce planning' => 'Workforce Planning',
            'hris' => 'HRIS',
            'workday' => 'Workday',
            'bamboohr' => 'BambooHR',
            'guidepoint' => 'Guidepoint',
            'people operations' => 'People Operations',
            
            // ============ LOGISTICS & SUPPLY CHAIN ============
            'supply chain' => 'Supply Chain Management',
            'supply chain management' => 'Supply Chain Management',
            'logistics' => 'Logistics',
            'inventory management' => 'Inventory Management',
            'warehouse management' => 'Warehouse Management',
            'procurement' => 'Procurement',
            'purchasing' => 'Purchasing',
            'vendor management' => 'Vendor Management',
            'freight management' => 'Freight Management',
            'distribution' => 'Distribution',
            'order fulfillment' => 'Order Fulfillment',
            'customs' => 'Customs Clearance',
            'import/export' => 'Import/Export',
            'trade compliance' => 'Trade Compliance',
            'erp' => 'ERP Systems',
            'sap' => 'SAP',
            'oracle' => 'Oracle',
            'jda' => 'JDA',
            'demand planning' => 'Demand Planning',
            'forecasting' => 'Forecasting',
            'route optimization' => 'Route Optimization',
            'gpms' => 'Global Positioning',
            'last mile delivery' => 'Last-Mile Delivery',
            'fleet management' => 'Fleet Management',
            'logistics planning' => 'Logistics Planning',
            'sustainability' => 'Sustainability',
            'green logistics' => 'Green Logistics',
            
            // ============ CONSTRUCTION & ENGINEERING ============
            'project management' => 'Project Management',
            'construction management' => 'Construction Management',
            'site management' => 'Site Management',
            'safety management' => 'Safety Management',
            'osha' => 'OSHA Compliance',
            'blueprints' => 'Blueprint Reading',
            'cad' => 'CAD Design',
            'autocad' => 'AutoCAD',
            'revit' => 'Revit',
            'civil engineering' => 'Civil Engineering',
            'structural engineering' => 'Structural Engineering',
            'electrical engineering' => 'Electrical Engineering',
            'mechanical engineering' => 'Mechanical Engineering',
            'plumbing' => 'Plumbing',
            'hvac' => 'HVAC',
            'carpentry' => 'Carpentry',
            'welding' => 'Welding',
            'heavy equipment operation' => 'Heavy Equipment Operation',
            'forklift' => 'Forklift Operation',
            'cost estimation' => 'Cost Estimation',
            'budgeting' => 'Budgeting',
            'material science' => 'Material Science',
            'quality assurance' => 'Quality Assurance',
            'building codes' => 'Building Codes',
            'regulatory compliance' => 'Regulatory Compliance',
            'bim' => 'BIM',
            'building information modeling' => 'Building Information Modeling',
            'estimating software' => 'Estimating Software',
            'primavera' => 'Primavera',
            'ms project' => 'MS Project',
            'sustainability' => 'Sustainability',
            'leed' => 'LEED Certification',
            'green building' => 'Green Building',
            
            // ============ MANUFACTURING & OPERATIONS ============
            'operations management' => 'Operations Management',
            'production management' => 'Production Management',
            'quality control' => 'Quality Control',
            'quality assurance' => 'Quality Assurance',
            'lean manufacturing' => 'Lean Manufacturing',
            'six sigma' => 'Six Sigma',
            'kaizen' => 'Kaizen',
            'process improvement' => 'Process Improvement',
            'root cause analysis' => 'Root Cause Analysis',
            'maintenance' => 'Maintenance',
            'preventive maintenance' => 'Preventive Maintenance',
            'equipment operation' => 'Equipment Operation',
            'cnc machining' => 'CNC Machining',
            'food safety' => 'Food Safety',
            'haccp' => 'HACCP',
            'gmp' => 'GMP',
            'iso 9001' => 'ISO 9001',
            'iso 14001' => 'ISO 14001',
            'waste management' => 'Waste Management',
            'scheduling' => 'Production Scheduling',
            'capacity planning' => 'Capacity Planning',
            'demand forecasting' => 'Demand Forecasting',
            'merp' => 'MERP',
            'scada' => 'SCADA Systems',
            'plc programming' => 'PLC Programming',
            'industrial automation' => 'Industrial Automation',
            'robotics' => 'Robotics',
            'iot' => 'IoT',
            'sensors' => 'Sensors & Instrumentation',
            'calibration' => 'Calibration',
            'troubleshooting' => 'Troubleshooting',
            
            // ============ HOSPITALITY & FOOD SERVICE ============
            'hospitality' => 'Hospitality',
            'hotel management' => 'Hotel Management',
            'front desk' => 'Front Desk Operations',
            'housekeeping' => 'Housekeeping',
            'facilities management' => 'Facilities Management',
            'guest services' => 'Guest Services',
            'customer service' => 'Customer Service',
            'food service' => 'Food Service',
            'cooking' => 'Cooking',
            'culinary arts' => 'Culinary Arts',
            'menu development' => 'Menu Development',
            'food safety' => 'Food Safety',
            'servsafe' => 'ServSafe',
            'bartending' => 'Bartending',
            'mixology' => 'Mixology',
            'sommelier' => 'Sommelier',
            'restaurant management' => 'Restaurant Management',
            'catering' => 'Catering',
            'event planning' => 'Event Planning',
            'banquet management' => 'Banquet Management',
            'kitchen management' => 'Kitchen Management',
            'inventory control' => 'Inventory Control',
            'cost control' => 'Cost Control',
            'wine service' => 'Wine Service',
            'beverage management' => 'Beverage Management',
            'point of sale systems' => 'Point of Sale Systems',
            'pos' => 'Point of Sale Systems',
            'reservation systems' => 'Reservation Systems',
            'opentable' => 'OpenTable',
            'tourism' => 'Tourism',
            
            // ============ RETAIL ============
            'retail' => 'Retail',
            'retail management' => 'Retail Management',
            'store management' => 'Store Management',
            'sales' => 'Sales',
            'customer service' => 'Customer Service',
            'merchandising' => 'Merchandising',
            'visual merchandising' => 'Visual Merchandising',
            'inventory management' => 'Inventory Management',
            'stock control' => 'Stock Control',
            'cash handling' => 'Cash Handling',
            'point of sale' => 'Point of Sale',
            'pos' => 'Point of Sale',
            'shopify' => 'Shopify',
            'ecommerce' => 'E-commerce',
            'ecommerce management' => 'E-commerce Management',
            'online sales' => 'Online Sales',
            'customer relations' => 'Customer Relations',
            'loss prevention' => 'Loss Prevention',
            'security' => 'Security',
            'visual display' => 'Visual Display',
            'customer experience' => 'Customer Experience',
            'retail analytics' => 'Retail Analytics',
            'big commerce' => 'BigCommerce',
            'woocommerce' => 'WooCommerce',
            'magento' => 'Magento',
            
            // ============ REAL ESTATE ============
            'real estate' => 'Real Estate',
            'real estate sales' => 'Real Estate Sales',
            'property management' => 'Property Management',
            'tenant management' => 'Tenant Management',
            'lease management' => 'Lease Management',
            'property valuation' => 'Property Valuation',
            'appraisal' => 'Appraisal',
            'market analysis' => 'Market Analysis',
            'negotiation' => 'Negotiation',
            'listing management' => 'Listing Management',
            'virtual tours' => 'Virtual Tours',
            'zillow' => 'Zillow',
            'mls' => 'MLS',
            'commercial real estate' => 'Commercial Real Estate',
            'residential real estate' => 'Residential Real Estate',
            'investment analysis' => 'Investment Analysis',
            'roi' => 'ROI Analysis',
            'financing' => 'Real Estate Financing',
            'mortgage' => 'Mortgage',
            'escrow' => 'Escrow',
            'title services' => 'Title Services',
            'home inspection' => 'Home Inspection',
            'staging' => 'Home Staging',
            'property marketing' => 'Property Marketing',
            'social media marketing' => 'Social Media Marketing',
            
            // ============ AUTOMOTIVE ============
            'automotive' => 'Automotive',
            'vehicle maintenance' => 'Vehicle Maintenance',
            'repair' => 'Vehicle Repair',
            'diagnostics' => 'Vehicle Diagnostics',
            'electrical systems' => 'Electrical Systems',
            'engine repair' => 'Engine Repair',
            'transmission' => 'Transmission Repair',
            'brake systems' => 'Brake Systems',
            'suspension' => 'Suspension',
            'alignment' => 'Alignment',
            'detailing' => 'Detailing',
            'custom work' => 'Custom Work',
            'sales' => 'Sales',
            'dealership management' => 'Dealership Management',
            'inventory management' => 'Inventory Management',
            'service advising' => 'Service Advising',
            'parts management' => 'Parts Management',
            'fleet maintenance' => 'Fleet Maintenance',
            'heavy equipment repair' => 'Heavy Equipment Repair',
            'electric vehicles' => 'Electric Vehicles',
            'hybrid systems' => 'Hybrid Systems',
            'obd-ii' => 'OBD-II Diagnostics',
            'tooling' => 'Tooling Knowledge',
            'customer service' => 'Customer Service',
            'warranty management' => 'Warranty Management',
            
            // ============ AGRICULTURE ============
            'agriculture' => 'Agriculture',
            'farming' => 'Farming',
            'crop management' => 'Crop Management',
            'crop science' => 'Crop Science',
            'plant care' => 'Plant Care',
            'soil management' => 'Soil Management',
            'soil science' => 'Soil Science',
            'irrigation' => 'Irrigation',
            'pest management' => 'Pest Management',
            'disease management' => 'Disease Management',
            'livestock management' => 'Livestock Management',
            'animal husbandry' => 'Animal Husbandry',
            'dairy farming' => 'Dairy Farming',
            'poultry' => 'Poultry Management',
            'aquaculture' => 'Aquaculture',
            'fisheries' => 'Fisheries Management',
            'agronomy' => 'Agronomy',
            'horticulture' => 'Horticulture',
            'floristry' => 'Floristry',
            'landscaping' => 'Landscaping',
            'organic farming' => 'Organic Farming',
            'sustainability' => 'Sustainability',
            'conservation' => 'Conservation',
            'equipment operation' => 'Equipment Operation',
            'machinery' => 'Farm Machinery',
            'tractors' => 'Tractor Operation',
            'food preservation' => 'Food Preservation',
            'agricultural technology' => 'Agricultural Technology',
            'precision agriculture' => 'Precision Agriculture',
            'farm management' => 'Farm Management',
            'agricultural business' => 'Agricultural Business',
            'export' => 'Export Management',
            
            // ============ TRANSPORTATION & DRIVING ============
            'driving' => 'Driving',
            'commercial driving' => 'Commercial Driving',
            'cdl' => 'CDL',
            'truck driving' => 'Truck Driving',
            'bus driving' => 'Bus Driving',
            'taxi/rideshare' => 'Taxi/Rideshare',
            'delivery' => 'Delivery',
            'route planning' => 'Route Planning',
            'vehicle maintenance' => 'Vehicle Maintenance',
            'safety compliance' => 'Safety Compliance',
            'logbook management' => 'Logbook Management',
            'gps systems' => 'GPS Systems',
            'customer service' => 'Customer Service',
            'defensive driving' => 'Defensive Driving',
            'parking' => 'Parking Lot Management',
            'traffic laws' => 'Traffic Laws Knowledge',
            'load securing' => 'Load Securing',
            'hazmat' => 'Hazmat Transportation',
            'dangerous goods' => 'Dangerous Goods',
            'passenger safety' => 'Passenger Safety',
            'air transportation' => 'Air Transportation',
            'pilot' => 'Pilot License',
            'copilot' => 'Copilot',
            'flight attendant' => 'Flight Attendant',
            'maritime' => 'Maritime',
            'captain' => 'Captain',
            'navigation' => 'Navigation',
            'ship operation' => 'Ship Operation',
            'cargo handling' => 'Cargo Handling',
            
            // ============ CREATIVE & ENTERTAINMENT ============
            'video production' => 'Video Production',
            'photography' => 'Photography',
            'cinematography' => 'Cinematography',
            'editing' => 'Video Editing',
            'audio production' => 'Audio Production',
            'sound engineering' => 'Sound Engineering',
            'music production' => 'Music Production',
            'music composition' => 'Music Composition',
            'performance' => 'Performance',
            'acting' => 'Acting',
            'directing' => 'Directing',
            'screenwriting' => 'Screenwriting',
            'animation' => 'Animation',
            '3d modeling' => '3D Modeling',
            '3d animation' => '3D Animation',
            'motion graphics' => 'Motion Graphics',
            'visual effects' => 'Visual Effects',
            'vfx' => 'Visual Effects',
            'game development' => 'Game Development',
            'game design' => 'Game Design',
            'game programming' => 'Game Programming',
            'unity' => 'Unity',
            'unreal engine' => 'Unreal Engine',
            'godot' => 'Godot',
            'blender' => 'Blender',
            'maya' => 'Maya',
            '3ds max' => '3ds Max',
            'digital art' => 'Digital Art',
            'illustration' => 'Illustration',
            'character design' => 'Character Design',
            'concept art' => 'Concept Art',
            'comic creation' => 'Comic Creation',
            'art direction' => 'Art Direction',
            'creative direction' => 'Creative Direction',
            'brand design' => 'Brand Design',
            'logo design' => 'Logo Design',
            'web design' => 'Web Design',
            'graphic design' => 'Graphic Design',
            'typography' => 'Typography',
            'layout design' => 'Layout Design',
            'print design' => 'Print Design',
            'packaging design' => 'Packaging Design',
            'user experience design' => 'User Experience Design',
            'ux' => 'User Experience Design',
            'user interface design' => 'User Interface Design',
            'ui' => 'User Interface Design',
            'interaction design' => 'Interaction Design',
            'sound design' => 'Sound Design',
            'voice acting' => 'Voice Acting',
            'dubbing' => 'Dubbing',
            'subtitling' => 'Subtitling',
            'translation' => 'Translation',
            'localization' => 'Localization',
            'scriptwriting' => 'Scriptwriting',
            'poetry' => 'Poetry',
            'creative writing' => 'Creative Writing',
            'storytelling' => 'Storytelling',
            'narrative design' => 'Narrative Design',
            'broadcast' => 'Broadcasting',
            'radio' => 'Radio',
            'journalism' => 'Journalism',
            'news production' => 'News Production',
            'documentary' => 'Documentary Production',
            'live event' => 'Live Event Production',
            'stage management' => 'Stage Management',
            'lighting design' => 'Lighting Design',
            'set design' => 'Set Design',
            'costume design' => 'Costume Design',
            'makeup' => 'Makeup Artistry',
            'hair styling' => 'Hair Styling',
            'fashion' => 'Fashion',
            'fashion design' => 'Fashion Design',
            'sewing' => 'Sewing',
            'garment construction' => 'Garment Construction',
            'pattern making' => 'Pattern Making',
            'fashion merchandising' => 'Fashion Merchandising',
            
            // ============ SPORTS & FITNESS ============
            'coaching' => 'Coaching',
            'athletic training' => 'Athletic Training',
            'personal training' => 'Personal Training',
            'fitness' => 'Fitness',
            'exercise physiology' => 'Exercise Physiology',
            'sports medicine' => 'Sports Medicine',
            'injury prevention' => 'Injury Prevention',
            'rehabilitation' => 'Rehabilitation',
            'nutrition' => 'Nutrition & Dietetics',
            'dietetics' => 'Nutrition & Dietetics',
            'strength training' => 'Strength Training',
            'conditioning' => 'Conditioning',
            'yoga' => 'Yoga',
            'pilates' => 'Pilates',
            'sports psychology' => 'Sports Psychology',
            'mental coaching' => 'Mental Coaching',
            'performance analysis' => 'Performance Analysis',
            'video analysis' => 'Video Analysis',
            'equipment management' => 'Equipment Management',
            'sports management' => 'Sports Management',
            'team management' => 'Team Management',
            'event organization' => 'Event Organization',
            'sports marketing' => 'Sports Marketing',
            'sponsorship' => 'Sponsorship Management',
            'sports law' => 'Sports Law',
            'officiating' => 'Officiating',
            'refereeing' => 'Refereeing',
            'sport specific skills' => 'Sport Specific Skills',
            'basketball' => 'Basketball',
            'soccer' => 'Soccer',
            'football' => 'American Football',
            'baseball' => 'Baseball',
            'tennis' => 'Tennis',
            'golf' => 'Golf',
            'swimming' => 'Swimming',
            'track and field' => 'Track and Field',
            'wrestling' => 'Wrestling',
            'martial arts' => 'Martial Arts',
            'boxing' => 'Boxing',
            'mma' => 'Mixed Martial Arts',
            'cycling' => 'Cycling',
            'skiing' => 'Skiing',
            'skateboarding' => 'Skateboarding',
            'surfing' => 'Surfing',
            'rock climbing' => 'Rock Climbing',
            'outdoors' => 'Outdoor Activities',
            'hiking' => 'Hiking',
            'camping' => 'Camping',
            'mountaineering' => 'Mountaineering',
            'wilderness survival' => 'Wilderness Survival',
            'orienteering' => 'Orienteering',
            'scuba diving' => 'Scuba Diving',
            'free diving' => 'Free Diving',
            'aviation' => 'Aviation',
            'dance' => 'Dance',
            'ballet' => 'Ballet',
            'contemporary' => 'Contemporary Dance',
            'hip hop' => 'Hip Hop Dance',
            'latin' => 'Latin Dance',
            'ballroom' => 'Ballroom Dance',
            'zumba' => 'Zumba',
            
            // ============ SECURITY & LAW ENFORCEMENT ============
            'security' => 'Security',
            'security officer' => 'Security Officer',
            'armed security' => 'Armed Security',
            'loss prevention' => 'Loss Prevention',
            'surveillance' => 'Surveillance',
            'cctv' => 'CCTV',
            'access control' => 'Access Control',
            'patrol' => 'Patrol',
            'investigative skills' => 'Investigative Skills',
            'law enforcement' => 'Law Enforcement',
            'police' => 'Police Work',
            'detective' => 'Detective Work',
            'criminal investigation' => 'Criminal Investigation',
            'forensics' => 'Forensics',
            'crime scene' => 'Crime Scene Analysis',
            'evidence collection' => 'Evidence Collection',
            'interrogation' => 'Interrogation',
            'custody' => 'Custodial Management',
            'corrections' => 'Corrections',
            'correctional officer' => 'Correctional Officer',
            'probation' => 'Probation/Parole',
            'legal knowledge' => 'Legal Knowledge',
            'court procedures' => 'Court Procedures',
            'constitutional law' => 'Constitutional Law',
            'criminal law' => 'Criminal Law',
            'self defense' => 'Self Defense',
            'tactical operations' => 'Tactical Operations',
            'swat' => 'SWAT Training',
            'conflict de-escalation' => 'Conflict De-escalation',
            'crisis intervention' => 'Crisis Intervention',
            'emergency response' => 'Emergency Response',
            'first aid' => 'First Aid',
            'cpr' => 'CPR',
            'aed' => 'AED',
            'hazmat' => 'Hazmat Response',
            'bomb detection' => 'Bomb Detection',
            'k9 handling' => 'K9 Handling',
            'drone operation' => 'Drone Operation',
            'technology skills' => 'Technology Skills',
            'database management' => 'Database Management',
            'report writing' => 'Report Writing',
            'public speaking' => 'Public Speaking',
            'community relations' => 'Community Relations',
            'cultural competency' => 'Cultural Competency',
            'diversity' => 'Diversity & Inclusion',
            
            // ============ UTILITY & TRADE SERVICES ============
            'electrical' => 'Electrical Work',
            'electrician' => 'Electrician',
            'plumbing' => 'Plumbing',
            'hvac' => 'HVAC',
            'carpentry' => 'Carpentry',
            'masonry' => 'Masonry',
            'roofing' => 'Roofing',
            'painting' => 'Painting',
            'landscaping' => 'Landscaping',
            'general labor' => 'General Labor',
            'heavy equipment' => 'Heavy Equipment Operation',
            'forklift' => 'Forklift Operation',
            'equipment repair' => 'Equipment Repair',
            'machinery' => 'Machinery Maintenance',
            'troubleshooting' => 'Troubleshooting',
            'mechanical work' => 'Mechanical Work',
            'welding' => 'Welding',
            'metal fabrication' => 'Metal Fabrication',
            'tool knowledge' => 'Tool Knowledge',
            'blueprint reading' => 'Blueprint Reading',
            'safety compliance' => 'Safety Compliance',
            'osha' => 'OSHA Compliance',
            'permits' => 'Permits & Licensing',
            'licensing' => 'Licensing & Certification',
            'apprenticeship' => 'Apprenticeship',
            'journeyman' => 'Journeyman',
            'master tradesman' => 'Master Tradesman',
            'customer service' => 'Customer Service',
            'job estimation' => 'Job Estimation',
            'invoicing' => 'Invoicing & Billing',
            'material sourcing' => 'Material Sourcing',
            'quality inspection' => 'Quality Inspection',
            'waste management' => 'Waste Management',
            'recycling' => 'Recycling Knowledge',
            'emergency repair' => 'Emergency Repair',
            'maintenance contracts' => 'Maintenance Contracts',
            'commercial work' => 'Commercial Work',
            'residential work' => 'Residential Work',
            'industrial work' => 'Industrial Work',
            
            // ============ ENVIRONMENTAL & SUSTAINABILITY ============
            'environmental science' => 'Environmental Science',
            'sustainability' => 'Sustainability',
            'conservation' => 'Conservation',
            'climate action' => 'Climate Action',
            'renewable energy' => 'Renewable Energy',
            'solar energy' => 'Solar Energy',
            'wind energy' => 'Wind Energy',
            'waste management' => 'Waste Management',
            'recycling' => 'Recycling',
            'water management' => 'Water Management',
            'pollution control' => 'Pollution Control',
            'environmental compliance' => 'Environmental Compliance',
            'epa' => 'EPA Compliance',
            'environmental audit' => 'Environmental Audit',
            'lifecycle assessment' => 'Lifecycle Assessment',
            'carbon footprint' => 'Carbon Footprint Analysis',
            'environmental health' => 'Environmental Health',
            'occupational health' => 'Occupational Health & Safety',
            'ohs' => 'Occupational Health & Safety',
            'hazard assessment' => 'Hazard Assessment',
            'risk mitigation' => 'Risk Mitigation',
            'green building' => 'Green Building',
            'leed' => 'LEED Certification',
            'energy efficiency' => 'Energy Efficiency',
            'environmental justice' => 'Environmental Justice',
            'restoration' => 'Environmental Restoration',
            'remediation' => 'Site Remediation',
            'ecology' => 'Ecology',
            'biodiversity' => 'Biodiversity',
            'forestry' => 'Forestry',
            'wildlife management' => 'Wildlife Management',
            'natural resource management' => 'Natural Resource Management',
            
            // ============ LEGAL ============
            'law' => 'Law',
            'legal' => 'Legal Services',
            'attorney' => 'Attorney',
            'lawyer' => 'Lawyer',
            'paralegal' => 'Paralegal',
            'legal assistant' => 'Legal Assistant',
            'contract law' => 'Contract Law',
            'corporate law' => 'Corporate Law',
            'employment law' => 'Employment Law',
            'criminal law' => 'Criminal Law',
            'family law' => 'Family Law',
            'estate planning' => 'Estate Planning',
            'intellectual property' => 'Intellectual Property',
            'patent law' => 'Patent Law',
            'trademark law' => 'Trademark Law',
            'copyright law' => 'Copyright Law',
            'real estate law' => 'Real Estate Law',
            'tax law' => 'Tax Law',
            'business law' => 'Business Law',
            'litigation' => 'Litigation',
            'trial advocacy' => 'Trial Advocacy',
            'legal research' => 'Legal Research',
            'legal writing' => 'Legal Writing',
            'brief writing' => 'Brief Writing',
            'contract drafting' => 'Contract Drafting',
            'legal negotiation' => 'Legal Negotiation',
            'mediation' => 'Mediation',
            'arbitration' => 'Arbitration',
            'legal compliance' => 'Legal Compliance',
            'regulatory knowledge' => 'Regulatory Knowledge',
            'legal documentation' => 'Legal Documentation',
            'courtroom procedure' => 'Courtroom Procedure',
            'case management' => 'Case Management',
            'legal software' => 'Legal Software',
            'westlaw' => 'Westlaw',
            'lexisnexis' => 'LexisNexis',
            'clio' => 'Clio',
            'imanage' => 'iManage',
            'legal ethics' => 'Legal Ethics',
            'confidentiality' => 'Confidentiality',
            
            // ============ GOVERNMENT & PUBLIC ADMINISTRATION ============
            'public administration' => 'Public Administration',
            'government' => 'Government Services',
            'civil service' => 'Civil Service',
            'policy' => 'Policy Development',
            'program management' => 'Program Management',
            'grants management' => 'Grants Management',
            'budget management' => 'Budget Management',
            'public finance' => 'Public Finance',
            'fiscal management' => 'Fiscal Management',
            'public procurement' => 'Public Procurement',
            'contract management' => 'Contract Management',
            'compliance' => 'Compliance',
            'audit' => 'Audit',
            'regulatory compliance' => 'Regulatory Compliance',
            'transparency' => 'Government Transparency',
            'public records' => 'Public Records Management',
            'records management' => 'Records Management',
            'document management' => 'Document Management',
            'archives' => 'Archives Management',
            'freedom of information' => 'Freedom of Information',
            'foil' => 'FOIL Requests',
            'data management' => 'Data Management',
            'database management' => 'Database Management',
            'gis' => 'GIS',
            'geographic information systems' => 'Geographic Information Systems',
            'community engagement' => 'Community Engagement',
            'public relations' => 'Public Relations',
            'stakeholder management' => 'Stakeholder Management',
            'intergovernmental relations' => 'Intergovernmental Relations',
            'strategic planning' => 'Strategic Planning',
            'organizational development' => 'Organizational Development',
            'performance measurement' => 'Performance Measurement',
            'outcomes assessment' => 'Outcomes Assessment',
            'program evaluation' => 'Program Evaluation',
            'benchmarking' => 'Benchmarking',
            'technology management' => 'Technology Management',
            'information technology' => 'Information Technology',
            'change management' => 'Change Management',
            'leadership' => 'Leadership',
            'management' => 'Management',
            'diversity and inclusion' => 'Diversity & Inclusion',
            'equity' => 'Equity & Access',
            'social justice' => 'Social Justice',
            'community development' => 'Community Development',
            'urban planning' => 'Urban Planning',
            'emergency management' => 'Emergency Management',
            'disaster response' => 'Disaster Response',
            'resilience' => 'Resilience Planning',
            'long-range planning' => 'Long-Range Planning',
            
            // ============ GENERAL PROFESSIONAL SKILLS ============
            'communication' => 'Communication',
            'written communication' => 'Written Communication',
            'verbal communication' => 'Verbal Communication',
            'interpersonal skills' => 'Interpersonal Skills',
            'listening' => 'Active Listening',
            'empathy' => 'Empathy',
            'emotional intelligence' => 'Emotional Intelligence',
            'soft skills' => 'Soft Skills',
            'hard skills' => 'Hard Skills',
            'technical skills' => 'Technical Skills',
            'time management' => 'Time Management',
            'organization' => 'Organization',
            'attention to detail' => 'Attention to Detail',
            'accuracy' => 'Accuracy',
            'multitasking' => 'Multitasking',
            'prioritization' => 'Prioritization',
            'deadline management' => 'Deadline Management',
            'self management' => 'Self Management',
            'work ethic' => 'Work Ethic',
            'reliability' => 'Reliability',
            'responsibility' => 'Responsibility',
            'accountability' => 'Accountability',
            'integrity' => 'Integrity',
            'honesty' => 'Honesty',
            'professionalism' => 'Professionalism',
            'adaptability' => 'Adaptability',
            'flexibility' => 'Flexibility',
            'learning agility' => 'Learning Agility',
            'resilience' => 'Resilience',
            'stress management' => 'Stress Management',
            'anger management' => 'Anger Management',
            'conflict resolution' => 'Conflict Resolution',
            'negotiation' => 'Negotiation',
            'persuasion' => 'Persuasion',
            'influence' => 'Influence',
            'persuasive writing' => 'Persuasive Writing',
            'presentation' => 'Presentation Skills',
            'public speaking' => 'Public Speaking',
            'facilitation' => 'Facilitation',
            'active listening' => 'Active Listening',
            'feedback' => 'Feedback',
            'coaching' => 'Coaching',
            'mentoring' => 'Mentoring',
            'teaching' => 'Teaching',
            'training' => 'Training',
            'coaching' => 'Coaching',
            'delegation' => 'Delegation',
            'decision making' => 'Decision Making',
            'critical thinking' => 'Critical Thinking',
            'analytical thinking' => 'Analytical Thinking',
            'logical thinking' => 'Logical Thinking',
            'systems thinking' => 'Systems Thinking',
            'strategic thinking' => 'Strategic Thinking',
            'creative thinking' => 'Creative Thinking',
            'innovation' => 'Innovation',
            'ideation' => 'Ideation',
            'brainstorming' => 'Brainstorming',
            'problem solving' => 'Problem Solving',
            'research' => 'Research',
            'data analysis' => 'Data Analysis',
            'attention to detail' => 'Attention to Detail',
            'quality' => 'Quality',
            'accuracy' => 'Accuracy',
            'precision' => 'Precision',
            'error checking' => 'Error Checking',
            'proofreading' => 'Proofreading',
            'editing' => 'Editing',
            'fact checking' => 'Fact Checking',
            'verification' => 'Verification',
            'validation' => 'Validation',
            'testing' => 'Testing',
            'quality assurance' => 'Quality Assurance',
            'continuous improvement' => 'Continuous Improvement',
            'kaizen' => 'Kaizen',
            'lean' => 'Lean Methodology',
            'six sigma' => 'Six Sigma',
            'agile' => 'Agile Methodology',
            'scrum' => 'Scrum',
            'kanban' => 'Kanban',
            'waterfall' => 'Waterfall Methodology',
            'risk management' => 'Risk Management',
            'change management' => 'Change Management',
            'knowledge management' => 'Knowledge Management',
            'information management' => 'Information Management',
            'documentation' => 'Documentation',
            'record keeping' => 'Record Keeping',
            'database' => 'Database',
            'spreadsheet' => 'Spreadsheet',
            'data entry' => 'Data Entry',
            'data management' => 'Data Management',
            'filing' => 'Filing & Organization',
            'correspondence' => 'Correspondence',
            'customer service' => 'Customer Service',
            'customer satisfaction' => 'Customer Satisfaction',
            'customer retention' => 'Customer Retention',
            'customer engagement' => 'Customer Engagement',
            'client relations' => 'Client Relations',
            'relationship building' => 'Relationship Building',
            'networking' => 'Networking',
            'partnership development' => 'Partnership Development',
            'collaboration' => 'Collaboration',
            'teamwork' => 'Teamwork',
            'team leadership' => 'Team Leadership',
            'group dynamics' => 'Group Dynamics',
            'cooperative work' => 'Cooperative Work',
            'mutual support' => 'Mutual Support',
        ];
    }
    
    /**
     * Normalize a skill name
     * Returns canonical skill name
     */
    public function normalize($skill) {
        $skill = trim($skill);
        $lower = strtolower($skill);
        
        // Check if exact match exists
        if (isset($this->normalizations[$lower])) {
            return $this->normalizations[$lower];
        }
        
        // If not found, return skill with standard capitalization
        return ucwords(strtolower($skill));
    }
    
    /**
     * Get normalized skill with confidence score
     * Returns array with normalization details
     */
    public function normalizeWithConfidence($skill) {
        $skill = trim($skill);
        $lower = strtolower($skill);
        
        if (isset($this->normalizations[$lower])) {
            return [
                'input' => $skill,
                'normalized' => $this->normalizations[$lower],
                'confidence' => 1.0,
                'matched' => true
            ];
        }
        
        // Check for partial matches
        $suggestion = $this->findClosestMatch($lower);
        
        if ($suggestion) {
            return [
                'input' => $skill,
                'normalized' => $this->normalizations[$suggestion],
                'confidence' => 0.75,
                'matched' => false
            ];
        }
        
        return [
            'input' => $skill,
            'normalized' => ucwords(strtolower($skill)),
            'confidence' => 0.0,
            'matched' => false
        ];
    }
    
    /**
     * Find closest match using Levenshtein distance
     */
    private function findClosestMatch($skill) {
        $closest = null;
        $shortest = PHP_INT_MAX;
        
        foreach (array_keys($this->normalizations) as $key) {
            $lev = levenshtein($skill, $key);
            
            if ($lev == 0) {
                return $key;
            }
            
            if ($lev < $shortest && $lev <= 3) {
                $closest = $key;
                $shortest = $lev;
            }
        }
        
        return $closest;
    }
    
    /**
     * Get all available canonical skills
     */
    public function getAllCanonicalSkills() {
        return array_unique(array_values($this->normalizations));
    }
    
    /**
     * Suggest skills based on input
     * Returns matching skills
     */
    public function suggestSkills($partial, $limit = 8) {
        $partial = strtolower(trim($partial));
        if (strlen($partial) < 1) {
            return [];
        }
        
        $suggestions = [];
        
        // First pass: starts with
        foreach (array_unique(array_values($this->normalizations)) as $skill) {
            if (stripos($skill, $partial) === 0) {
                $suggestions[] = $skill;
                if (count($suggestions) >= $limit) {
                    return array_unique($suggestions);
                }
            }
        }
        
        // Second pass: contains
        if (count($suggestions) < $limit) {
            foreach (array_unique(array_values($this->normalizations)) as $skill) {
                if (count($suggestions) >= $limit) break;
                if (stripos($skill, $partial) !== false && !in_array($skill, $suggestions)) {
                    $suggestions[] = $skill;
                }
            }
        }
        
        return array_unique($suggestions);
    }

    /**
     * Find which category a skill belongs to (via synonym matching)
     * Returns category info or null
     */
    public function findSkillCategory($skill, $conn) {
        $skill_normalized = strtolower(trim($skill));
        
        // Check canonical names first
        $sql = "SELECT category_id, category_name, canonical_name FROM skill_categories 
                WHERE LOWER(canonical_name) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $skill_normalized);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return [
                'category_id' => $row['category_id'],
                'category_name' => $row['category_name'],
                'canonical_name' => $row['canonical_name'],
                'matched_by' => 'canonical'
            ];
        }
        
        // Check synonyms
        $sql = "SELECT c.category_id, c.category_name, c.canonical_name 
                FROM skill_categories c 
                JOIN skill_synonyms s ON c.category_id = s.category_id 
                WHERE LOWER(s.synonym) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $skill_normalized);
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return [
                'category_id' => $row['category_id'],
                'category_name' => $row['category_name'],
                'canonical_name' => $row['canonical_name'],
                'matched_by' => 'synonym'
            ];
        }
        
        return null;
    }

    /**
     * Check if two skills match (considering synonyms)
     * Returns match details with score and type
     */
    public function checkSkillMatch($skill1, $skill2, $conn) {
        $skill1_lower = strtolower(trim($skill1));
        $skill2_lower = strtolower(trim($skill2));
        
        // Exact match
        if ($skill1_lower === $skill2_lower) {
            return [
                'match' => true,
                'match_type' => 'exact',
                'score' => 100,
                'skill1' => $skill1,
                'skill2' => $skill2
            ];
        }
        
        // Get category for each skill
        $cat1 = $this->findSkillCategory($skill1, $conn);
        $cat2 = $this->findSkillCategory($skill2, $conn);
        
        // Both found in same category = synonym match
        if ($cat1 && $cat2 && $cat1['category_id'] === $cat2['category_id']) {
            return [
                'match' => true,
                'match_type' => 'synonym',
                'score' => 70,
                'skill1' => $skill1,
                'skill2' => $skill2,
                'category' => $cat1['category_name'],
                'canonical' => $cat1['canonical_name']
            ];
        }
        
        // Check partial/Levenshtein match
        $lev = levenshtein($skill1_lower, $skill2_lower);
        if ($lev > 0 && $lev <= 3) {
            return [
                'match' => true,
                'match_type' => 'partial',
                'score' => 50,
                'skill1' => $skill1,
                'skill2' => $skill2,
                'distance' => $lev
            ];
        }
        
        return [
            'match' => false,
            'match_type' => 'none',
            'score' => 0,
            'skill1' => $skill1,
            'skill2' => $skill2
        ];
    }

    /**
     * Get canonical form of a skill (from database or fallback to normalization)
     */
    public function getCanonicalForm($skill, $conn) {
        $category = $this->findSkillCategory($skill, $conn);
        if ($category) {
            return $category['canonical_name'];
        }
        
        // Fallback to built-in normalization
        return $this->normalize($skill);
    }

    /**
     * Get all synonyms for a skill
     */
    public function getSkillSynonyms($skill, $conn) {
        $category = $this->findSkillCategory($skill, $conn);
        
        if (!$category) {
            return [];
        }
        
        $sql = "SELECT synonym FROM skill_synonyms WHERE category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $category['category_id']);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $synonyms = [];
        while ($row = $result->fetch_assoc()) {
            $synonyms[] = $row['synonym'];
        }
        
        return $synonyms;
    }
}
?>
