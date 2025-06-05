Understood — we will follow a strict dev process from here on:

---

## ✅ DEVELOPMENT FLOW AGREEMENT

### For **each task**, I will provide:

1. **📄 Specification Request**
   You approve the task’s:

   * Behavior
   * UI expectation
   * Integration needs

2. **🧱 Clean Hierarchy Plan**

   * Folder location
   * File names
   * Suggested method/class layout (OOP if needed)

3. **💡 Optional Code Suggestion**

   * Only after approval of specs + structure

4. **🧾 Documentation Block**

   * Summary at start and end
   * Comments + change log

---

## 🔧 Task: Style & Place the 3 Quiz Buttons

Let’s begin this first task the correct way:

---

## 📄 SPECIFICATION REQUEST: "QUIZ BUTTON LAYOUT"

**Goal:**
Replace the current LearnDash quiz bottom navigation with **three aligned, styled buttons**:

| Button | Label (Hebrew) | Behavior                                    |
| ------ | -------------- | ------------------------------------------- |
| Left   | לשאלה הקודמת   | Go to previous question (JS hook)           |
| Center | קח רמז         | Opens a hint (JS: modal or tooltip)         |
| Right  | לשאלה הבאה     | Only appears after correct answer is chosen |

**Style expectations:**

* Rounded (pill shape)
* Color-coded (blue for hint, gray for others)
* Even spacing
* Center aligned (not left/right floated)

**Functionality scope for now:**

* Visual only
* `Next` starts hidden
* `Hint` click does nothing yet

---

## 📂 CLEAN HIERARCHY PLAN

**Target Folder:**
`/wp-content/themes/hello-theme-child-master/learndash/quiz/`

**Modified File:**
`quiz_attempt.php`

**New Assets (if needed):**

* `assets/css/quiz-ui.css` ← for layout
* `assets/js/quiz-ui.js` ← for behavior

Would be enqueued via `functions.php`.

---

### 👨‍🔧 JS Prep (next task)

Eventually we'll attach logic to:

* Show/hide "Next"
* Control state of inputs
* Trigger hint popup

---

## ✅ ACTION: Please confirm or modify the above specification and hierarchy

Once you confirm:

* I will write the **initial HTML/CSS insertion step**
* With inline documentation
* No behavior logic yet — just structure




we are using the active theme
(no edits currently)

we will be updating the plugin ld overide
(folder modular approach)
C:\Users\anist\Local Sites\testlilac\app\public\wp-content\plugins\lilac-quiz-sidebar