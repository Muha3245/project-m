<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default"
  data-assets-path="../assets/" data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>{{ config('app.name') }}</title>

  <meta name="description" content="" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="user-id" content="{{ auth()->id() }}">


  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet" />

  <!-- Icons. Uncomment required icon fonts -->
  <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/fonts/boxicons.css') }}" />

  <!-- Core CSS -->
  <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
  <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/css/theme-default.css') }}"
    class="template-customizer-theme-css" />
  <link rel="stylesheet" href="{{ asset('sneat/assets/css/demo.css') }}" />

  <!-- Vendors CSS -->
  <link rel="stylesheet" href="{{ asset('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

  <!-- Page CSS -->
  @yield('page-styles')

  <!-- Helpers -->
  <script src="{{ asset('sneat/assets/vendor/js/helpers.js') }}"></script>

  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <script src="{{ asset('sneat/assets/js/config.js') }}"></script>

  @vite(['resources/js/app.js'])
</head>

<body>
  <!-- Layout wrapper -->
  <div id="notification-container"
      style="
              position: fixed;
              top: 20px;
              right: 20px;
              width: 300px;
              z-index: 9999;
          ">
  </div>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      <!-- Menu -->
      @include('layouts.sidebar')
      <!-- / Menu -->
      
      <!-- Layout container -->
      <div class="layout-page">
        <!-- Navbar -->
        @include('layouts.navbar')
        <!-- / Navbar -->
        

        <!-- Content wrapper -->
        <div class="content-wrapper">
          
          <!-- Content -->
          @yield('page-content')
          <!-- / Content -->

          <div class="content-backdrop fade"></div>
        </div>
        <!-- Content wrapper -->
      </div>
      <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>
  </div>
  <!-- / Layout wrapper -->


  <!-- Core JS -->
  <!-- build:js assets/vendor/js/core.js -->
  <script src="{{ asset('sneat/assets/vendor/libs/jquery/jquery.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/libs/popper/popper.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/js/bootstrap.js') }}"></script>
  <script src="{{ asset('sneat/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

  <script src="{{ asset('sneat/assets/vendor/js/menu.js') }}"></script>
  <!-- endbuild -->

  <!-- Vendors JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Main JS -->
  <script src="{{ asset('sneat/assets/js/main.js') }}"></script>
  <script>
    // Handle click events for call and video icons
    $(document).on("click", ".call-icon, .video-icon", function() {
        var type = $(this).data("type"); // Get call type (audio or video)
        var receiverId = $(this).data("receiver-id"); // Get receiver ID

        // Ensure receiverId is a valid number
        receiverId = parseInt(receiverId);
        if (!receiverId || isNaN(receiverId)) {
            alert("Error: The receiver ID is not available.");
            return;
        }

        console.log("Receiver ID:", receiverId); // Debugging

        // Send call notification via AJAX
        $.ajax({
            url: '/send-call-notification',
            type: 'POST',
            data: {
                receiver_id: receiverId, // Receiver ID
                call_type: type, // Call type (audio or video)
                _token: $('meta[name="csrf-token"]').attr('content') // CSRF token
            },
            success: function(response) {
                console.log('Call notification sent:', response);
                // Redirect to the call page after sending the notification
                window.location.href = "/call/" + receiverId + "/" + type;
            },
            error: function(xhr) {
                console.error('Error sending call notification:', xhr.responseText);
                alert('Failed to send call notification. Please try again.');
            }
        });
    });

    // Fetch and display notifications
    function checkNotifications() {
        $.ajax({
            url: "/fetch-notifications",
            type: "GET",
            success: function(notifications) {
                let notificationContainer = $("#notification-container");
                notificationContainer.empty(); // Clear old notifications

                notifications.forEach(notification => {
                    let notificationData = notification.data;

                    // Create a unique ID for each notification to avoid duplicates
                    let notificationId = `notification-${notification.id}`;

                    // Check if the notification already exists in the DOM
                    if (!$(`#${notificationId}`).length) {
                        let notificationDiv = `
                            <div id="${notificationId}" style="background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); margin-bottom: 10px;">
                                <strong>${notificationData.message}</strong>
                                <br>
                                <button onclick="acceptCall(${notificationData.sender_id}, '${notificationData.call_type}', '${notification.id}')" style="background: green; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; margin-top: 5px;">Accept</button>
                                <button onclick="dismissNotification('${notification.id}')" style="background: red; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; margin-top: 5px;">Decline</button>
                            </div>
                        `;

                        notificationContainer.append(notificationDiv);
                    }
                });
            },
            error: function(xhr) {
                console.error("Error fetching notifications:", xhr.responseText);
            }
        });
    }

    // Handle accepting a call
    function acceptCall(senderId, callType, notificationId) {
        console.log("Accepting call from:", senderId);

        // Mark notification as read before redirecting
        $.ajax({
            url: "/mark-notification-read",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                notification_id: notificationId
            },
            success: function() {
                console.log("Notification marked as read.");
                // Redirect to the call page
                window.location.href = "/call/" + senderId + "/" + callType;
            },
            error: function(xhr) {
                console.error("Error marking notification as read:", xhr.responseText);
                // Still proceed to call in case of error
                window.location.href = "/call/" + senderId + "/" + callType;
            }
        });
    }

    // Handle dismissing a notification
    function dismissNotification(notificationId) {
        $(`#notification-${notificationId}`).remove(); // Remove the notification from the DOM

        // Mark notification as read
        $.ajax({
            url: "/mark-notification-read",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                notification_id: notificationId
            },
            success: function(response) {
                console.log("Notification dismissed and marked as read.");
            },
            error: function(xhr) {
                console.error("Error dismissing notification:", xhr.responseText);
            }
        });
    }

    // Poll for notifications every 5 seconds
    // setInterval(checkNotifications, 5000);

    // Initial check for notifications when the page loads
    $(document).ready(function() {
        checkNotifications();
    });
</script>
  <!-- Page JS -->
  @yield('page-scripts')
  <!-- Place this tag in your head or just before your close body tag. -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>

  @include('layouts.sweetalert')
</body>

</html>
