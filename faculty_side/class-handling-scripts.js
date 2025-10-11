document.addEventListener("DOMContentLoaded", () => {
    // Select elements
    const createOutputBtn = document.getElementById('createOutputBtn');
    const createOutputContainer = document.querySelector('.create-output-container');
    const confirmOutputBtn = document.getElementById('confirmOutputBtn');
    const cancelOutputBtn = document.getElementById('cancelOutputBtn');
    const outputsList = document.getElementById('outputsList');

    // Show the create output container
    createOutputBtn.addEventListener('click', () => {
        createOutputContainer.classList.remove('hidden');
    });

    // Hide the create output container
    cancelOutputBtn.addEventListener('click', () => {
        createOutputContainer.classList.add('hidden');
    });

    // ...existing code...
    // ...existing code...

    function fetchAndDisplayOutputs(classId) {
        fetch(`fetch_class_outputs.php?class_id=${encodeURIComponent(classId)}`)
            .then(response => response.json())
            .then(data => {
                outputsList.innerHTML = '';
                if (data.success && data.outputs.length > 0) {
                    data.outputs.forEach(output => {
                        const listItem = document.createElement('li');
                        listItem.dataset.outputId = output.output_id; // <-- Add this line
                        listItem.innerHTML = `
                            <span>${output.output_name} (Total Score: ${output.total_score})</span>
                            <div class="input-and-buttons">
                                <input type="number" placeholder="Enter your score" class="user-score" disabled />
                                <div class="button-group">
                                    <button class="editOutput-btn">Edit</button>
                                    <button class="delete-btn">Delete</button>
                                </div>
                            </div>
                        `;
                        outputsList.appendChild(listItem);
                    });
                }
            });
    }

    // ...existing code...
    confirmOutputBtn.addEventListener('click', () => {
        const outputName = document.getElementById('outputName').value.trim();
        const totalScore = document.getElementById('totalScore').value.trim();
        const urlParams = new URLSearchParams(window.location.search);
        const classId = urlParams.get('class_id');
        const editingOutputId = confirmOutputBtn.dataset.editingOutputId;
    
        if (outputName === '' || totalScore === '') {
            alert('Please fill out all fields.');
            return;
        }
    
        if (editingOutputId) {
            // Edit existing output
            fetch('edit_class_output.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `output_id=${encodeURIComponent(editingOutputId)}&output_name=${encodeURIComponent(outputName)}&total_score=${encodeURIComponent(totalScore)}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    fetchAndDisplayOutputs(classId);
                    document.getElementById('outputName').value = '';
                    document.getElementById('totalScore').value = '';
                    createOutputContainer.classList.add('hidden');
                    delete confirmOutputBtn.dataset.editingOutputId;
                } else {
                    alert('Failed to update output.');
                }
            });
        } else {
            // Add new output (existing code)
            fetch('add_class_output.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `class_id=${encodeURIComponent(classId)}&output_name=${encodeURIComponent(outputName)}&total_score=${encodeURIComponent(totalScore)}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    fetchAndDisplayOutputs(classId);
                    document.getElementById('outputName').value = '';
                    document.getElementById('totalScore').value = '';
                    createOutputContainer.classList.add('hidden');
                } else {
                    alert('Failed to add output.');
                }
            });
        }
    });
    
    // ...existing code...

    // Use event delegation for Edit and Delete buttons
    outputsList.addEventListener('click', (event) => {
        const target = event.target;
    
        // Handle Edit button click
        if (target.classList.contains('editOutput-btn')) {
            const listItem = target.closest('li');
            const outputId = listItem.dataset.outputId; // Make sure to set this in your fetchAndDisplayOutputs!
            const outputDetails = listItem.querySelector('span').textContent;
            const [outputName, totalScore] = outputDetails
                .match(/^(.*) \(Total Score: (\d+)\)$/)
                .slice(1);
    
            // Populate the input fields with the existing values
            document.getElementById('outputName').value = outputName;
            document.getElementById('totalScore').value = totalScore;
    
            // Show the create output container for editing
            createOutputContainer.classList.remove('hidden');
    
            // Save the outputId for editing
            confirmOutputBtn.dataset.editingOutputId = outputId;
        }
    
        // Handle Delete button click
        if (target.classList.contains('delete-btn')) {
            const listItem = target.closest('li');
            const outputId = listItem.dataset.outputId;
            if (confirm("Are you sure you want to delete this output?")) {
                fetch('delete_class_output.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `output_id=${encodeURIComponent(outputId)}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        listItem.remove();
                    } else {
                        alert("Failed to delete output.");
                    }
                });
            }
        }
    });

    // Deadline Logic Frontend:
    const setDeadlineBtn = document.querySelector('.class-actions button:nth-child(2)');
    const setDeadlineContainer = document.querySelector('.set-deadline-container');
    const confirmDeadlineBtn = document.getElementById('confirmDeadlineBtn');
    const cancelDeadlineBtn = document.getElementById('cancelDeadlineBtn');
    

    // Show the set deadline container
    setDeadlineBtn.addEventListener('click', () => {
        setDeadlineContainer.classList.remove('hidden');
    });

    // Hide the set deadline container
    cancelDeadlineBtn.addEventListener('click', () => {
        setDeadlineContainer.classList.add('hidden');
    });

    

    const showRequirementInputBtn = document.getElementById("showRequirementInputBtn");
    const addRequirementContainer = document.querySelector(".add-requirement-container");
    const requirementInput = document.getElementById("requirementInput");
    const addRequirementBtn = document.getElementById("addRequirementBtn");
    const requirementsList = document.getElementById("requirementsList");

    let editingRequirement = null; // Track the requirement being edited

    // Show the input container when "Add Requirement" button is clicked
    showRequirementInputBtn.addEventListener("click", () => {
        addRequirementContainer.classList.remove("hidden");
        showRequirementInputBtn.classList.add("hidden"); // Hide the "Add Requirement" button
    });

    // Add a new requirement
    addRequirementBtn.addEventListener("click", () => {
        const requirementText = requirementInput.value.trim();

        if (requirementText === "") {
            alert("Please type a requirement.");
            return;
        }

        if (editingRequirement) {
            // Update the existing requirement
            editingRequirement.querySelector(".requirement-text").textContent = requirementText;
            editingRequirement = null; // Reset the editing state
        } else {
            // Create a new list item for the requirement
            const listItem = document.createElement("li");
            listItem.innerHTML = `
                <span class="requirement-text">${requirementText}</span>
                <div class="requirement-actions">
                    <button class="edit-requirement-btn">Edit</button>
                    <button class="delete-requirement-btn">Delete</button>
                </div>
            `;
          // Append the new requirement to the list
          requirementsList.appendChild(listItem);
        }  

        // Clear the input field and hide the input container
        requirementInput.value = "";
        addRequirementContainer.classList.add("hidden");
        showRequirementInputBtn.classList.remove("hidden"); // Show the "Add Requirement" button again
    });

    // Use event delegation for Edit and Delete buttons
    requirementsList.addEventListener("click", (event) => {
        const target = event.target;

        // Handle Edit button click
        if (target.classList.contains("edit-requirement-btn")) {
            const listItem = target.closest("li");
            const requirementText = listItem.querySelector(".requirement-text").textContent;

            // Populate the input field with the existing requirement
            requirementInput.value = requirementText;

            // Show the input container for editing
            addRequirementContainer.classList.remove("hidden");
            showRequirementInputBtn.classList.add("hidden");

            // Set the editing state
            editingRequirement = listItem;
        }

        // Handle Delete button click
        if (target.classList.contains("delete-requirement-btn")) {
            const listItem = target.closest("li");
            listItem.remove();
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const classId = urlParams.get('class_id');

    if (classId) {
        fetch("fetch_classes.php")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Find the class with the matching ID
                    const classItem = data.classes.find(c => c.class_id == classId);
                    if (classItem) {
                        // Format the class name as you want it to appear
                        const classNameDisplay = `${classItem.course_name} ${classItem.class_name} Term ${classItem.term_number} ${classItem.start_year}-${classItem.end_year}`;
                        // Update the breadcrumb
                        document.querySelector('.breadcrumb-container .class-name').textContent = classNameDisplay;
                    }
                }
            });
    }
    
        // ...existing code...
    if (classId) {
        fetchAndDisplayOutputs(classId);
        // Fetch class details and display them
        fetch(`fetch_class_details.php?class_id=${classId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const details = data.details;
                    document.querySelector('.class-details').innerHTML = `
                        <h3>CLASS DETAILS</h3>
                        <p><strong>Course Instructor:</strong> [Show instructor here]</p>
                        <p><strong>Program:</strong> ${details.program_name}</p>
                        <p><strong>Course Name:</strong> ${details.course_name}</p>
                        <p><strong>Class:</strong> ${details.class_name}</p>
                        <h3>REQUIREMENTS</h3>
                        <p>List here the ff. requirements:</p>
                        <ul id="requirementsList"></ul>
                        <button id="showRequirementInputBtn" class="action-btn">Add Requirement</button>
                        <div class="add-requirement-container hidden">
                            <input type="text" id="requirementInput" placeholder="Type a requirement" />
                            <button id="addRequirementBtn" class="action-btn">Add</button>
                        </div>
                        <p id="deadlineDisplay"><strong>Deadline:</strong> Not set</p>
                        
                    `;

                    // ...existing code...
                    const deadlineDisplay = document.getElementById('deadlineDisplay');
                    if (details.deadline_at && details.deadline_at !== '0000-00-00 00:00:00') {
                        const deadline = new Date(details.deadline_at.replace(' ', 'T'));
                        const dateStr = deadline.toISOString().slice(0, 10);
                        let hours = deadline.getHours();
                        const minutes = String(deadline.getMinutes()).padStart(2, '0');
                        const period = hours >= 12 ? 'PM' : 'AM';
                        const formattedHours = hours % 12 || 12;
                        deadlineDisplay.innerHTML = `<strong>Deadline:</strong> ${dateStr} at ${formattedHours}:${minutes} ${period}`;
                    } else {
                        deadlineDisplay.innerHTML = `<strong>Deadline:</strong> Not set`;
                    }
    
                    // Fetch requirements from backend and display them
                                        
                    fetch(`fetch_requirement_desc.php?class_id=${classId}`)
                        .then(response => response.json())
                        .then(reqData => {
                            if (reqData.success) {
                                const requirementsList = document.getElementById("requirementsList");
                                requirementsList.innerHTML = ""; // <-- Clear the list first!
                                reqData.requirements.forEach(req => {
                                    const listItem = document.createElement("li");
                                    listItem.dataset.requirementId = req.requirement_id;
                                    listItem.innerHTML = `
                                        <span class="requirement-text">${req.requirement_desc}</span>
                                        <div class="requirement-actions">
                                            <button class="edit-requirement-btn">Edit</button>
                                            <button class="delete-requirement-btn">Delete</button>
                                        </div>
                                    `;
                                    requirementsList.appendChild(listItem);
                                });
                            }
                        });
                    // ...existing code...
    
                    // Re-select and re-attach event listeners for requirements
                    const showRequirementInputBtn = document.getElementById("showRequirementInputBtn");
                    const addRequirementContainer = document.querySelector(".add-requirement-container");
                    const requirementInput = document.getElementById("requirementInput");
                    const addRequirementBtn = document.getElementById("addRequirementBtn");
                    const requirementsList = document.getElementById("requirementsList");
                    let editingRequirement = null;
    
                    showRequirementInputBtn.addEventListener("click", () => {
                        addRequirementContainer.classList.remove("hidden");
                        showRequirementInputBtn.classList.add("hidden");
                    });
    
                                        // ...existing code...
                    requirementsList.addEventListener("click", (event) => {
                        const target = event.target;
                    
                        // Handle Edit button click
                        if (target.classList.contains("edit-requirement-btn")) {
                            const listItem = target.closest("li");
                            const requirementText = listItem.querySelector(".requirement-text").textContent;
                            requirementInput.value = requirementText;
                            addRequirementContainer.classList.remove("hidden");
                            showRequirementInputBtn.classList.add("hidden");
                            editingRequirement = listItem;
                        }
                    
                        // Handle Delete button click
                        if (target.classList.contains("delete-requirement-btn")) {
                            const listItem = target.closest("li");
                            const requirementId = listItem.dataset.requirementId;
                            if (confirm("Are you sure you want to delete this requirement?")) {
                                fetch('delete_requirement_desc.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: `requirement_id=${encodeURIComponent(requirementId)}`
                                })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.success) {
                                        listItem.remove();
                                    } else {
                                        alert("Failed to delete requirement.");
                                    }
                                });
                            }
                        }
                    });
                    
                    // Update Add/Edit button logic:
                    addRequirementBtn.addEventListener("click", () => {
                        const requirementText = requirementInput.value.trim();
                        if (requirementText === "") {
                            alert("Please type a requirement.");
                            return;
                        }
                        if (editingRequirement) {
                            // Edit existing requirement in backend
                            const requirementId = editingRequirement.dataset.requirementId;
                            fetch('edit_requirement_desc.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `requirement_id=${encodeURIComponent(requirementId)}&requirement_desc=${encodeURIComponent(requirementText)}`
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    editingRequirement.querySelector(".requirement-text").textContent = requirementText;
                                    editingRequirement = null;
                                    requirementInput.value = "";
                                    addRequirementContainer.classList.add("hidden");
                                    showRequirementInputBtn.classList.remove("hidden");
                                } else {
                                    alert("Failed to update requirement.");
                                }
                            });
                        } else {
                            // Add new requirement (already implemented)
                            fetch('add_requirement_desc.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `class_id=${encodeURIComponent(classId)}&requirement_desc=${encodeURIComponent(requirementText)}`
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    const listItem = document.createElement("li");
                                    listItem.dataset.requirementId = result.requirement_id;
                                    listItem.innerHTML = `
                                        <span class="requirement-text">${requirementText}</span>
                                        <div class="requirement-actions">
                                            <button class="edit-requirement-btn">Edit</button>
                                            <button class="delete-requirement-btn">Delete</button>
                                        </div>
                                    `;
                                    requirementsList.appendChild(listItem);
                                    requirementInput.value = "";
                                    addRequirementContainer.classList.add("hidden");
                                    showRequirementInputBtn.classList.remove("hidden");
                                } else {
                                    alert("Failed to add requirement.");
                                }
                            });
                        }
                    });
                    

                    confirmDeadlineBtn.addEventListener('click', () => {
                    const deadlineDate = document.getElementById('deadlineDate').value;
                    const deadlineTime = document.getElementById('deadlineTime').value;
                
                    if (deadlineDate === '' || deadlineTime === '') {
                        alert('Please select both date and time.');
                        return;
                    }
                
                    const deadline_at = `${deadlineDate} ${deadlineTime}:00`;
                
                    fetch('set_deadline.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `class_id=${encodeURIComponent(classId)}&deadline_at=${encodeURIComponent(deadline_at)}`
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            // Fetch the updated class details to refresh the deadline display
                            fetch(`fetch_class_details.php?class_id=${classId}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        const details = data.details;
                                        const deadlineDisplay = document.getElementById('deadlineDisplay');
                                        if (details.deadline_at && details.deadline_at !== '0000-00-00 00:00:00') {
                                            const deadline = new Date(details.deadline_at.replace(' ', 'T'));
                                            const dateStr = deadline.toISOString().slice(0, 10);
                                            let hours = deadline.getHours();
                                            const minutes = String(deadline.getMinutes()).padStart(2, '0');
                                            const period = hours >= 12 ? 'PM' : 'AM';
                                            const formattedHours = hours % 12 || 12;
                                            deadlineDisplay.innerHTML = `<strong>Deadline:</strong> ${dateStr} at ${formattedHours}:${minutes} ${period}`;
                                        } else {
                                            deadlineDisplay.innerHTML = `<strong>Deadline:</strong> Not set`;
                                        }
                                        setDeadlineContainer.classList.add('hidden');
                                    }
                                });
                        } else {
                            alert('Failed to set deadline.');
                        }
                    });
                });
                }
            });
        }
    // ...existing code...

});