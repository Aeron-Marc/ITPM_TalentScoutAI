# Skill Synonym System - Testing Guide

## Overview
The skill synonym system uses a centralized skill database with synonym matching across all modules. Weight distribution:
- **100%** = Exact match
- **70%** = Synonym match (normalized to same canonical skill)
- **50%** = Partial match (contains skill word)

---

## Phase 1: Test Data Setup (In Admin Dashboard)

### Step 1: Create Test Skill Categories
1. Go to **PESO Dashboard → Skill Management**
2. Click **"Add Category"**
3. Create these test categories:

#### Category 1: Web Development
- **Category Name:** Web Development Roles
- **Canonical Name:** web developer
- **Synonyms:** 
  - web programmer
  - frontend developer
  - website developer
  - web designer
  - full stack developer

#### Category 2: Database
- **Category Name:** Database Administration
- **Canonical Name:** database administrator
- **Synonyms:**
  - database manager
  - DBA
  - database expert
  - SQL specialist

#### Category 3: Project Management
- **Category Name:** Project Management
- **Canonical Name:** project manager
- **Synonyms:**
  - product manager
  - scrum master
  - project lead
  - team lead

### Step 2: Verify Test Matching (Optional)
1. Go to **Test Matching** tab in Skill Management
2. Test combinations:
   - "web developer" vs "web programmer" → Should show **Match: synonym (70%)**
   - "web developer" vs "frontend developer" → Should show **Match: synonym (70%)**
   - "database administrator" vs "DBA" → Should show **Match: synonym (70%)**
   - "web developer" vs "database administrator" → Should show **No Match**

---

## Phase 2: Resume Builder Testing

### Test Objective
Verify that skills added to resumes are normalized and matched against skill categories.

### Test Steps

**As a Job Seeker (Employee):**

1. **Create a Resume with Normalized Skills**
   - Go to **Employees Dashboard → Resume Builder**
   - Create a new resume
   - In the **Skills section**, add these skills one by one:
     ```
     Skills to add:
     1. Web Programmer          (should normalize to "web developer")
     2. Frontend Developer      (should normalize to "web developer")
     3. Database Manager        (should normalize to "database administrator")
     4. Team Lead               (should normalize to "project manager")
     5. Unknown Skill           (should NOT match any category)
     ```

2. **Verify Normalization**
   - Save the resume
   - Open developer console (F12)
   - Check **Network tab** for the skill submission request
   - Look for the **normalized skills** being sent to the server
   - Reload the resume and verify skills are saved correctly

**What to Check:**
- ✅ Skills are saved without errors
- ✅ Page shows confirmation message
- ✅ Skills persist after page reload
- ✅ Browser console has no JavaScript errors
- ✅ Network tab shows successful API responses (200 status)

---

## Phase 3: Job Posts Testing

### Test Objective
Verify that job requirements are normalized when posting jobs and can match with normalized employee skills.

### Test Steps

**As an Employer:**

1. **Create a Job Post with Skill Requirements**
   - Go to **Employers Dashboard → Post Jobs**
   - Create a new job post
   - Fill in basic info (title, description, location)
   - In the **Required Skills** section, add:
     ```
     Skills to add:
     1. Web Programming         (synonym - should normalize to "web developer")
     2. Database Admin          (synonym - should normalize to "database administrator")
     3. Product Manager         (synonym - should normalize to "project manager")
     4. Linux Administration    (no matching category)
     ```

2. **Verify Job Post Creation**
   - Save the job post
   - Open the job post details
   - Check that all skills are properly saved
   - Verify page shows no errors

**What to Check:**
- ✅ Job post saves successfully
- ✅ All skills appear in the job post
- ✅ Skills are properly displayed on the job details page
- ✅ Network tab shows successful responses

---

## Phase 4: Skill Gap Analysis Testing

### Test Objective
Verify that skill gap analysis compares normalized employee skills against normalized job requirements with synonym matching.

### Test Steps

**As a Job Seeker (Employee):**

1. **View Skill Gap for a Job Post**
   - Go to **Employees Dashboard → Skill Gap Analysis** (or find job post and click skill gap)
   - Search for or select the job post created in Phase 3
   - View the skill comparison

2. **Interpret the Results**
   - Should show a table like:
     ```
     Job Skill                  Your Status                   Match Score
     ───────────────────────────────────────────────────────────────────
     Web Developer              ✓ You have: Web Programmer    70% (Synonym)
     Database Administrator     ✗ You don't have this skill   0%
     Project Manager            ✓ You have: Team Lead         70% (Synonym)
     Linux Administration       ✗ You don't have this skill   0%
     ```

3. **Verify Matching Logic**
   - Your "Web Programmer" skill should match job's "Web Programming" as 70% (synonym)
   - Your "Team Lead" should match job's "Product Manager" as 70% (synonym)
   - Missing skills should show as unmatched
   - Overall match percentage should reflect weighted average

**What to Check:**
- ✅ Synonym matches show 70% score (not 100%)
- ✅ Skills you have show checkmark (✓)
- ✅ Skills you don't have show missing mark (✗)
- ✅ Match percentages are calculated correctly
- ✅ No JavaScript errors in console
- ✅ Page loads without API errors

---

## Phase 5: AI Matching Test (Advanced)

### Test Objective
Verify that the AI matching engine uses normalized skills with synonym weights.

### Test Steps

**As a Job Seeker:**

1. **Use AI Job Matching**
   - Go to **Employees Dashboard → AI Matching** (or similar feature)
   - View recommended jobs based on your resume skills
   - The job posts from Phase 3 should appear if you have matching skills

2. **Verify Ranking**
   - Jobs should be ranked by:
     - Exact matches (100%) rank higher
     - Synonym matches (70%) rank medium
     - Partial matches (50%) rank lower
   - Expected ranking for our test skills:
     1. Jobs requiring "web developer" (you have "web programmer")
     2. Jobs requiring "database admin" (0% match)
     3. Jobs requiring "linux" (0% match)

**What to Check:**
- ✅ Recommended jobs include those with synonym matches
- ✅ Jobs are properly ranked by match score
- ✅ Match percentages are shown
- ✅ No errors in calculations

---

## Phase 6: Database Verification (Advanced)

### Verify Data is Stored Correctly

**Check Resume Skills (MySQL):**
```sql
SELECT * FROM job_seeker_skills 
WHERE job_seeker_id = [USER_ID];
```
- Verify normalized_skill is properly stored

**Check Job Requirements:**
```sql
SELECT * FROM job_requirements 
WHERE job_id = [JOB_ID];
```
- Verify all skills are saved

**Check Skill Normalization:**
```sql
SELECT 
  s.skill_name,
  n.normalized_skill,
  c.canonical_name,
  c.category_name
FROM job_seeker_skills s
LEFT JOIN skill_normalizer_cache n ON s.skill_name = n.original_skill
LEFT JOIN skill_categories c ON n.category_id = c.category_id;
```

---

## Expected Behavior Checklist

### Resume Builder
- [ ] Skills are saved without errors
- [ ] Skills persist across page reloads
- [ ] Normalized skills are stored in database
- [ ] Network requests show 200 status codes
- [ ] No console errors

### Job Posts
- [ ] All job post fields save correctly
- [ ] Skills are properly added to job requirements
- [ ] Job post displays all skills on view page
- [ ] Can edit job post and skills
- [ ] Can delete individual skills from job

### Skill Gap Analysis
- [ ] Page loads job details correctly
- [ ] Displays your skills from resume
- [ ] Shows required skills from job
- [ ] Calculates match percentages accurately
- [ ] Synonym matches show 70% (not 100%)
- [ ] Overall match percentage is reasonable

### AI Matching
- [ ] Recommended jobs appear based on your skills
- [ ] Jobs are ranked by match score
- [ ] Synonym matches appear in results
- [ ] No errors in matching algorithm

---

## Debugging Tips

### If Skills Don't Normalize:
1. Check **Browser Console (F12)** → Console tab for errors
2. Check **Network tab** for API request/response
3. Go to **Skill Management → Test Matching** and verify the synonym exists
4. Check database: `SELECT * FROM skill_synonyms WHERE synonym LIKE '%[skill_name]%';`

### If Matching Doesn't Work:
1. Verify skill categories exist: `SELECT * FROM skill_categories;`
2. Verify synonyms are linked: `SELECT * FROM skill_synonyms;`
3. Test in Skill Management → Test Matching tab
4. Check PHP error logs in XAMPP

### If Match Score is Wrong:
1. Check match-engine.php logic:
   - Should be 100% for exact match
   - Should be 70% for synonym match
   - Should be 50% for partial match
2. Verify skill-normalizer.php methods are returning correct weights

---

## API Endpoints Used (Internal)

| Module | Endpoint | Purpose |
|--------|----------|---------|
| Resume Builder | `skills-api.php?action=add` | Save skill to resume |
| Job Posts | `post-jobs/index.php` | Save job requirements |
| Skill Gap | `skill-gap-analysis/index.php` | Compare skills |
| AI Matching | `ai-matching/match-engine.php` | Calculate matches |
| Skill Mgmt | `skill-management/api.php?action=get-categories` | Get available skills |

---

## Success Criteria

✅ **Resume Builder:** Skills normalize on save
✅ **Job Posts:** Requirements are saved and retrievable  
✅ **Skill Gap:** Shows 70% matches for synonyms (not 100%)
✅ **AI Matching:** Jobs ranked correctly with synonym consideration
✅ **No Errors:** Console and server logs are clean
✅ **Database:** All data persists correctly

