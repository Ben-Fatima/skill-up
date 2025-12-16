/**
 * Frontend controller for chunked file uploads and import progress tracking.
 * - Manages file selection state and disables input while processing.
 * - Streams the file in 10 MB chunks to `/upload/*` endpoints, updating UI progress.
 * - Polls `/import` to track processing progress and aggregates created/updated/failed counts.
 * - Renders the first few errors and links to the full CSV error report when available.
 */

const fileInput = document.getElementById("file-input");
const fileInfo = document.getElementById("file-info");
const uploadStatus = document.getElementById("upload-status");
const importStatus = document.getElementById("import-status");
const uploadBar = document.getElementById("upload-progress-bar");
const importBar = document.getElementById("import-progress-bar");
const messages = document.getElementById("messages");
const errorsContainer = document.getElementById("errors");

let selectedFile = null;
let collectedErrors = [];
let isProcessing = false;

if (fileInput) {
  fileInput.addEventListener("change", async () => {
    if (isProcessing) {
      return;
    }

    const [file] = fileInput.files;
    selectedFile = file || null;

    if (selectedFile) {
      collectedErrors = [];
      isProcessing = true;
      setInputDisabled(true);
      fileInfo.textContent = `${selectedFile.name} (${formatBytes(
        selectedFile.size
      )})`;
      errorsContainer.innerHTML = "";
      messages.textContent = "";
      importBar.style.width = "0%";
      importStatus.textContent = "";
      uploadBar.style.width = "0%";
      uploadStatus.textContent = "Initializing upload...";
      let importId = "";
      try {
        try {
          const uploadId = await initUpload(selectedFile);
          console.log("Initialized upload with ID:", uploadId);
          uploadStatus.textContent = "Upload initialized. Starting upload...";

          let offset = 0;
          const chunkSize = 1024 * 1024 * 10;
          let progress = 0;

          while (offset < selectedFile.size) {
            const chunk = selectedFile.slice(offset, offset + chunkSize);
            await uploadChunk(uploadId, offset, chunk);
            offset += chunk.size;
            progress = Math.min((offset / selectedFile.size) * 100, 100);
            uploadBar.style.width = `${progress}%`;
            uploadStatus.textContent = `Uploading... ${progress.toFixed(2)}%`;
          }

          const data = await uploadComplete(uploadId, selectedFile.name);
          importId = data.import_id;
          uploadStatus.textContent = `${data.message}`;
        } catch (error) {
          uploadStatus.textContent = `Upload failed: ${
            error?.message || "Unknown error"
          }`;
          return;
        }

        try {
          importStatus.textContent = "Starting import...";
          const state = await runImport(importId, selectedFile.size);
          messages.textContent = `Created: ${state.created}, Updated: ${state.updated}, Failed: ${state.failed}.`;
        } catch (error) {
          importStatus.textContent = `Import failed: ${
            error?.message || "Unknown error"
          }`;
        }
      } finally {
        setInputDisabled(false);
        isProcessing = false;
      }
    } else {
      fileInfo.textContent = "";
      uploadStatus.textContent = "Waiting for file…";
    }
  });
}

/**
 * Enable/disable the file input and toggle disabled styles.
 * @param {boolean} disabled
 */
function setInputDisabled(disabled) {
  fileInput.disabled = disabled;
  fileInput.classList.toggle("opacity-60", disabled);
  fileInput.classList.toggle("cursor-not-allowed", disabled);
}

/**
 * Convert a byte count to a human readable label.
 * @param {number} bytes
 * @returns {string}
 */
function formatBytes(bytes) {
  if (bytes === 0) return "0 B";
  const k = 1024;
  const sizes = ["B", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  const value = bytes / Math.pow(k, i);
  return `${value.toFixed(i === 0 ? 0 : 2)} ${sizes[i]}`;
}

/**
 * Request an upload session from the server.
 * @param {File} file
 * @returns {Promise<string>} upload ID
 */
async function initUpload(file) {
  const res = await fetch("/upload/init", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      filename: file.name,
      fileSizeBytes: file.size,
    }),
  });

  if (!res.ok) {
    throw new Error(`Server responded with ${res.status}`);
  }

  const data = await res.json();
  return data.upload_id;
}

/**
 * Send a chunk to the upload endpoint.
 * @param {string} uploadId
 * @param {number} offset
 * @param {Blob} chunk
 * @returns {Promise<object>}
 */
async function uploadChunk(uploadId, offset, chunk) {
  const res = await fetch(
    `/upload/chunk?upload_id=${encodeURIComponent(uploadId)}&offset=${offset}`,
    { method: "POST", body: chunk }
  );

  if (!res.ok) {
    throw new Error(`Server responded with ${res.status}`);
  }
  const data = await res.json();
  return data;
}

/**
 * Finalize the upload after all chunks are sent.
 * @param {string} uploadId
 * @param {string} filename
 * @returns {Promise<object>}
 */
async function uploadComplete(uploadId, filename) {
  const res = await fetch(
    `/upload/complete?upload_id=${encodeURIComponent(
      uploadId
    )}&original_name=${encodeURIComponent(filename)}`,
    { method: "POST" }
  );
  if (!res.ok) {
    throw new Error(`Server responded with ${res.status}`);
  }
  const data = await res.json();
  return data;
}

/**
 * Poll the import endpoint until processing completes.
 * @param {string} importId
 * @param {number} fileSize
 * @returns {Promise<{created: number, updated: number, failed: number}>}
 */
async function runImport(importId, fileSize) {
  let done = false;
  let created = 0;
  let updated = 0;
  let failed = 0;

  while (!done) {
    let progress = 0;
    const res = await fetch(`/import?id=${encodeURIComponent(importId)}`, {
      method: "POST",
    });
    if (!res.ok) {
      const payload = await res.json();
      const message =
        payload?.error ||
        payload?.message ||
        `Server responded with ${res.status}`;
      const err = new Error(message);
      err.status = res.status;
      throw err;
    }
    const data = await res.json();

    progress = Math.min((data.bytes_processed / fileSize) * 100, 100);
    importBar.style.width = `${progress}%`;
    importStatus.textContent = `Importing... ${progress.toFixed(2)}%`;
    created += data.created;
    updated += data.updated;
    failed += data.failed;

    if (data.errors && data.errors.length > 0) {
      collectedErrors.push(...data.errors);
    }

    if (data.done) {
      done = true;
      importStatus.textContent = `Import completed. Processed ${data.processed_rows} rows.`;
      importBar.style.width = `100%`;
      break;
    }
  }

  renderErrors(collectedErrors, importId, failed);

  return { created, updated, failed };
}

/**
 * Render a summary of failed rows and link to the CSV error report.
 * @param {Array} errors
 * @param {string} importId
 * @param {number} totalFailed
 */
function renderErrors(errors, importId, totalFailed) {
  errorsContainer.innerHTML = "";

  if (!totalFailed || totalFailed === 0) {
    return;
  }

  const visible = errors.slice(0, 5);

  const details = document.createElement("details");
  details.className = "mt-3 border border-red-200 bg-red-50/80 rounded-md p-3";

  const summary = document.createElement("summary");
  summary.className =
    "cursor-pointer text-sm font-semibold text-red-700 flex items-center justify-between";

  summary.innerHTML = `
    <span>${totalFailed} row${
    totalFailed > 1 ? "s" : ""
  } failed during import</span>
    <span class="text-xs text-red-500">Click to view first ${
      visible.length
    }</span>
  `;

  const body = document.createElement("div");
  body.className = "mt-3 space-y-3";

  const list = document.createElement("ul");
  list.className = "space-y-2 text-xs text-red-800";

  visible.forEach((error) => {
    const li = document.createElement("li");
    li.className = "border border-red-100 rounded px-2 py-1 bg-white/60";

    const messages =
      Array.isArray(error.errors) && error.errors.length
        ? error.errors.join(", ")
        : error.message || "Unknown error";

    li.innerHTML = `
      <div class="font-medium">
        Row ${error.row_number ?? "?"}
        ${error.sku ? `(SKU: ${error.sku})` : ""}
      </div>
      <div class="mt-0.5">${messages}</div>
      ${
        error.raw_row
          ? `<div class="mt-1 text-[10px] text-slate-500 break-words">
       Raw: ${error.raw_row}
     </div>`
          : ""
      }
    `;
    list.appendChild(li);
  });

  body.appendChild(list);

  if (totalFailed > visible.length) {
    const link = document.createElement("a");
    link.href = `/import/errors-report?import_id=${encodeURIComponent(
      importId
    )}`;
    link.className =
      "inline-flex items-center text-xs font-medium text-red-900 underline underline-offset-2";
    link.textContent = "Download full errors report (CSV)";
    body.appendChild(link);
  }

  details.appendChild(summary);
  details.appendChild(body);
  errorsContainer.appendChild(details);
}
