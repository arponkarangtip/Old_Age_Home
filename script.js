 // A simple chatbot that responds with some predefined answers
 function chatbot(input) {
    let output = "";
    input = input.toLowerCase();
    if (input.includes("hello") || input.includes("hi")) {
      output = "Hello, nice to meet you!";
    } else if (input.includes("do you know about oahm")) {
      output = "Thank you for asking.It is a very good platform";
    } else if (input.includes("how many residents live in the old age home")) {
      output = "There are 60 residents currently living in the old age home.";
    } else if (input.includes("what is the age range of the residents")) {
      output = "The residents ages range from 60 to 95 years.";
    } else if (input.includes("what is the gender distribution of residents")) {
      output = "There are 35 females and 25 males residing in the home.";
    } 
    else if (input.includes("how many staff members work there, and what are their roles")) {
      output = "There are 20 staff members";
    }else if (input.includes("what types of rooms are available (single/shared)")) {
      output = "TThe home has 20 single rooms and 20 shared rooms (each shared by 2 residents).";
    }
    else if (input.includes("are there medical facilities or an in house doctor")) {
      output = "Yes, there is an in-house doctor available for regular checkups, and a small medical clinic is attached to the home.";
    }
    else if (input.includes("is there a recreational area or outdoor space for residents")) {
      output = "es, there is a garden, a small park, and a recreational hall where residents can exercise, socialize, and participate in activities.";
    }
    else {
      output = "Sorry, I don't understand that. Please try something else.";
    }
    return output;
  }

  // Display the user message on the chat
  function displayUserMessage(message) {
    let chat = document.getElementById("chat");
    let userMessage = document.createElement("div");
    userMessage.classList.add("message");
    userMessage.classList.add("user");
    let userAvatar = document.createElement("div");
    userAvatar.classList.add("avatar");
    let userText = document.createElement("div");
    userText.classList.add("text");
    userText.innerHTML = message;
    userMessage.appendChild(userAvatar);
    userMessage.appendChild(userText);
    chat.appendChild(userMessage);
    chat.scrollTop = chat.scrollHeight;
  }

  // Display the bot message on the chat
  function displayBotMessage(message) {
    let chat = document.getElementById("chat");
    let botMessage = document.createElement("div");
    botMessage.classList.add("message");
    botMessage.classList.add("bot");
    let botAvatar = document.createElement("div");
    botAvatar.classList.add("avatar");
    let botText = document.createElement("div");
    botText.classList.add("text");
    botText.innerHTML = message;
    botMessage.appendChild(botAvatar);
    botMessage.appendChild(botText);
    chat.appendChild(botMessage);
    chat.scrollTop = chat.scrollHeight;
  }

  // Send the user message and get the bot response
  function sendMessage() {
    let input = document.getElementById("input").value;
    if (input) {
      displayUserMessage(input);
      let output = chatbot(input);
      setTimeout(function() {
        displayBotMessage(output);
      }, 1000);
      document.getElementById("input").value = "";
    }
  }

  // Add a click event listener to the button
  document.getElementById("button").addEventListener("click", sendMessage);

  // Add a keypress event listener to the input
  document.getElementById("input").addEventListener("keypress", function(event) {
    if (event.keyCode == 13) {
      sendMessage();
    }
  });