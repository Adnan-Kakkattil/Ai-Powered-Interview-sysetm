(() => {
  const editorContainer = document.getElementById("editor");
  const submitButton = document.getElementById("submit-code");

  if (!editorContainer) {
    return;
  }

  let editorInstance = null;

  const initializeMonaco = () => {
    if (!window.require) {
      console.warn("Monaco loader not available.");
      return;
    }

    window.require.config({
      paths: { vs: "/static/vendor/monaco/vs" },
    });

    window.require(["vs/editor/editor.main"], () => {
      editorInstance = monaco.editor.create(editorContainer, {
        value: "# Write your solution here\n",
        language: "python",
        theme: "vs-dark",
        automaticLayout: true,
      });
    });
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeMonaco);
  } else {
    initializeMonaco();
  }

  const initEyeDetection = async () => {
    const video = document.getElementById("eye-feed");
    const status = document.getElementById("eye-status");

    if (!navigator.mediaDevices?.getUserMedia) {
      status.textContent = "Webcam access is not supported in this browser.";
      return;
    }

    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: true });
      video.srcObject = stream;
      status.textContent = "Eye detection active.";
    } catch (error) {
      status.textContent = "Unable to access webcam.";
      console.error(error);
    }
  };

  initEyeDetection();

  if (submitButton) {
    submitButton.addEventListener("click", async () => {
      const assignmentId = editorContainer.dataset.assignmentId;
      const code = editorInstance ? editorInstance.getValue() : "";
      console.log("Submitting code for assignment", assignmentId, code);
      // TODO: wire axios/fetch call to backend endpoint
    });
  }
})();


