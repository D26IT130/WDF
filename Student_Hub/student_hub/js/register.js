// // Frontend validation for register.php.
// // The patterns below MUST match the constants at the top of register.php.
// // This improves usability only; the server is always the final authority.
// (function () {
//   'use strict';

//   var RX = {
//     name: /^[A-Za-z][A-Za-z .'-]{2,49}$/,
//     username: /^[A-Za-z0-9_]{4,20}$/,
//     mobile: /^[6-9][0-9]{9}$/,
//     password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,64}$/,
//     email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
//   };

//   var form = document.getElementById('registerForm');
//   if (!form) return;

//   // Each validator returns an error message, or '' when the value is fine.
//   var validators = {
//     full_name: function (v) {
//       return RX.name.test(v.trim()) ? '' : 'Enter 3 to 50 letters. Spaces, dots, hyphens and apostrophes are allowed.';
//     },
//     username: function (v) {
//       return RX.username.test(v.trim()) ? '' : 'Use 4 to 20 letters, numbers or underscores.';
//     },
//     email: function (v) {
//       v = v.trim();
//       return v.length <= 100 && RX.email.test(v) ? '' : 'Enter a valid email address, like name@example.com.';
//     },
//     mobile: function (v) {
//       return RX.mobile.test(v.trim()) ? '' : 'Enter a 10-digit mobile number starting with 6, 7, 8 or 9.';
//     },
//     password: function (v) {
//       return RX.password.test(v)
//         ? ''
//         : 'Use 8 to 64 characters with an uppercase letter, a lowercase letter, a number and a symbol.';
//     },
//     confirm_password: function (v) {
//       var pw = form.elements.password.value;
//       return v !== '' && v === pw ? '' : 'The passwords do not match.';
//     },
//     course: function (v) { return v ? '' : 'Choose your course.'; },
//     year: function (v) { return v ? '' : 'Choose your year of study.'; },
//     gender: function () {
//       return form.querySelector('input[name="gender"]:checked') ? '' : 'Choose a gender option.';
//     },
//     terms: function () {
//       return form.elements.terms.checked ? '' : 'You need to accept the terms to create an account.';
//     }
//   };

//   function showError(name, message) {
//     var box = document.getElementById(name + '-error');
//     if (!box) return;
//     box.textContent = message;
//     box.hidden = !message;

//     var fields = form.querySelectorAll('[name="' + name + '"]');
//     for (var i = 0; i < fields.length; i++) {
//       if (message) { fields[i].setAttribute('aria-invalid', 'true'); }
//       else { fields[i].removeAttribute('aria-invalid'); }
//     }
//   }

//   function validateField(name) {
//     var field = form.elements[name];
//     var value = field && field.type !== 'radio' && field.type !== 'checkbox' && field.value !== undefined ? field.value : '';
//     var message = validators[name](value);
//     showError(name, message);
//     return message === '';
//   }

//   // Real-time validation: on leaving a field, then live once it has been touched.
//   Object.keys(validators).forEach(function (name) {
//     var nodes = form.querySelectorAll('[name="' + name + '"]');
//     Array.prototype.forEach.call(nodes, function (node) {
//       node.addEventListener('blur', function () { validateField(name); });
//       node.addEventListener('change', function () { validateField(name); });
//       node.addEventListener('input', function () {
//         if (node.getAttribute('aria-invalid') === 'true') validateField(name);
//       });
//     });
//   });

//   // Re-check confirm password whenever the password changes.
//   form.elements.password.addEventListener('input', function () {
//     if (form.elements.confirm_password.value) validateField('confirm_password');
//   });

//   // Password strength meter
//   var bar = document.getElementById('strengthBar');
//   var text = document.getElementById('strengthText');
//   var LABELS = ['not entered', 'very weak', 'weak', 'fair', 'good', 'strong'];

//   function strengthScore(pw) {
//     if (!pw) return 0;
//     var score = 0;
//     if (pw.length >= 8) score++;
//     if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
//     if (/\d/.test(pw)) score++;
//     if (/[^A-Za-z0-9]/.test(pw)) score++;
//     if (pw.length >= 12) score++;
//     return Math.max(score, 1);
//   }

//   form.elements.password.addEventListener('input', function (e) {
//     var score = strengthScore(e.target.value);
//     bar.style.width = (score * 20) + '%';
//     bar.setAttribute('data-level', String(score));
//     text.textContent = LABELS[score];
//   });

//   // Validate everything on submit; block the request if anything fails.
//   form.addEventListener('submit', function (e) {
//     var firstInvalid = null;
//     Object.keys(validators).forEach(function (name) {
//       if (!validateField(name) && !firstInvalid) {
//         firstInvalid = form.querySelector('[name="' + name + '"]');
//       }
//     });
//     if (firstInvalid) {
//       e.preventDefault();
//       firstInvalid.focus();
//     }
//   });

//   // If the server sent back errors, move focus to the first one.
//   var serverError = form.querySelector('[aria-invalid="true"]');
//   if (serverError) serverError.focus();
// })();

// Practical 9: frontend validation + submit for registration_secure.html.
// Rules MUST match php/process_registration_db.php (the server is the final authority).





// (function () {
//   'use strict';

//   var ENDPOINT = '../php/process_registration_db.php';
//   var EMAIL_RX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

//   var form = document.getElementById('registerForm');
//   var statusBox = document.getElementById('formStatus');
//   var submitBtn = document.getElementById('submitBtn');
//   if (!form) return;

//   var validators = {
//     full_name: function (v) {
//       v = v.trim();
//       return v.length >= 3 && v.length <= 50 ? '' : 'Enter your full name (3 to 50 characters).';
//     },
//     email: function (v) {
//       v = v.trim();
//       if (v === '') return 'Email is required.';
//       return v.length <= 100 && EMAIL_RX.test(v) ? '' : 'Enter a valid email address, like name@example.com.';
//     },
//     password: function (v) {
//       return v.length >= 8 ? '' : 'Use at least 8 characters.';
//     },
//     confirm_password: function (v) {
//       return v !== '' && v === form.elements.password.value ? '' : 'The passwords do not match.';
//     }
//   };

//   function showError(name, message) {
//     var box = document.getElementById(name + '-error');
//     if (box) { box.textContent = message; box.hidden = !message; }
//     var field = form.elements[name];
//     if (!field) return;
//     if (message) field.setAttribute('aria-invalid', 'true');
//     else field.removeAttribute('aria-invalid');
//   }

//   function validateField(name) {
//     var message = validators[name](form.elements[name].value);
//     showError(name, message);
//     return message === '';
//   }

//   function showStatus(type, message) {
//     statusBox.className = 'alert ' + type;
//     statusBox.textContent = message;
//     statusBox.hidden = false;
//   }

//   // Real-time validation: when leaving a field, then live once it shows an error
//   Object.keys(validators).forEach(function (name) {
//     var input = form.elements[name];
//     input.addEventListener('blur', function () { validateField(name); });
//     input.addEventListener('input', function () {
//       if (input.getAttribute('aria-invalid') === 'true') validateField(name);
//     });
//   });
//   form.elements.password.addEventListener('input', function () {
//     if (form.elements.confirm_password.value) validateField('confirm_password');
//   });

//   form.addEventListener('submit', function (e) {
//     e.preventDefault();

//     var firstInvalid = null;
//     Object.keys(validators).forEach(function (name) {
//       if (!validateField(name) && !firstInvalid) firstInvalid = form.elements[name];
//     });
//     if (firstInvalid) { firstInvalid.focus(); return; }

//     statusBox.hidden = true;
//     submitBtn.disabled = true;
//     submitBtn.textContent = 'Creating account...';

//     fetch(ENDPOINT, { method: 'POST', body: new FormData(form) })
//       .then(function (res) {
//         return res.text().then(function (raw) {
//           try { return JSON.parse(raw); }
//           catch (err) {
//             console.error('Server did not return JSON:', raw);
//             throw new Error('The server sent an unexpected reply. Open the browser Console (F12) to see it. Make sure you opened this page through http://localhost/.');
//           }
//         });
//       })
//       .then(function (data) {
//         if (data.success) {
//           showStatus('success', data.message);
//           form.reset();
//           Object.keys(validators).forEach(function (n) { showError(n, ''); });
//           return;
//         }
//         if (data.errors) {
//           var first = null;
//           Object.keys(data.errors).forEach(function (n) {
//             showError(n, data.errors[n]);
//             if (!first) first = form.elements[n];
//           });
//           if (first) first.focus();
//         }
//         showStatus('error', data.message || 'Registration failed.');
//       })
//       .catch(function (err) { showStatus('error', err.message); })
//       .then(function () {
//         submitBtn.disabled = false;
//         submitBtn.textContent = 'Create account';
//       });
//   });
// })();







(function () {
    'use strict';

    // Use your existing registration backend.
    var ENDPOINT = '../php/register.php';

    var form = document.getElementById('registerForm');
    var statusBox = document.getElementById('formStatus');
    var submitBtn = document.getElementById('submitBtn');

    if (!form) {
        console.error('Registration form with id="registerForm" was not found.');
        return;
    }

    function showStatus(type, message) {
        if (!statusBox) {
            alert(message);
            return;
        }

        statusBox.className = 'alert ' + type;
        statusBox.textContent = message;
        statusBox.hidden = false;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        var fullName = form.elements.fullName.value.trim();
        var email = form.elements.email.value.trim();
        var studentId = form.elements.studentId.value.trim();
        var password = form.elements.password.value;
        var confirmPassword = form.elements.confirm_password.value;
        var terms = form.elements.terms.checked;

        if (fullName.length < 3 || fullName.length > 100) {
            showStatus('error', 'Enter a name between 3 and 100 characters.');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showStatus('error', 'Enter a valid email address.');
            return;
        }

        if (!studentId) {
            showStatus('error', 'Enter your student ID.');
            return;
        }

        if (password.length < 6 || password.length > 72) {
            showStatus('error', 'Password must be between 6 and 72 characters.');
            return;
        }

        if (password !== confirmPassword) {
            showStatus('error', 'Passwords do not match.');
            return;
        }

        if (!terms) {
            showStatus('error', 'Please accept the Terms & Conditions.');
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating account...';
        }

        try {
            var response = await fetch(ENDPOINT, {
                method: 'POST',
                body: new FormData(form)
            });

            var data = await response.json();

            if (!response.ok) {
                showStatus('error', data.message || 'Registration failed.');
                return;
            }

            showStatus(
                'success',
                data.message || 'Registration successful!'
            );

            form.reset();

        } catch (error) {
            console.error('Registration error:', error);

            showStatus(
                'error',
                'Could not reach the registration server. Check XAMPP and the browser console.'
            );

        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create Account';
            }
        }
    });
})();
