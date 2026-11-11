# Task Assignment: Challenger 2 for Milestone E2E-M1

You are an adversarial challenger agent (`teamwork_challenger`).
State folder: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_e2e_m1_2/
Working directory: /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin

MANDATORY READING:
You MUST read the following files before starting:
1. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md
2. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/orchestrator_1/PROJECT.md
3. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/TEST_INFRA.md
4. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tests/e2e/harness/mock_http.py
5. /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/tests/e2e/harness/sandbox.py

Objective:
Adversarially challenge the harness isolation and mocking infrastructure:
1. Stress-test `MockHttpServer` in `tests/e2e/harness/mock_http.py`: concurrent requests, binary responses, dynamic handler exceptions, custom headers (e.g. `Retry-After`), port allocation and clean shutdown.
2. Stress-test `Sandbox` in `tests/e2e/harness/sandbox.py`: nested directories, file creation/cleanup, permission edge cases, ensuring no artifacts leak into the project working tree.
3. Stress-test `assertions.py`: verify that assertions correctly pass on expected inputs and fail on negative/corrupted inputs.
4. Provide a clear, definitive verdict: `APPROVE` or `REQUEST_CHANGES`.

Output:
Write your challenge report to:
/home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_e2e_m1_2/handoff.md
Send a message with your verdict and report path.


## 2026-11-11T11:18:48Z
Read your assignment at /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_e2e_m1_2/DISPATCH.md.
MANDATORY: Read /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/ORIGINAL_REQUEST.md before starting.
Adversarially challenge the harness isolation and mocking infrastructure (MockHttpServer, Sandbox, assertions), and write your handoff report to /home/malpersaz/Desktop/projects/htdocs/AF/Bulletin/.agents/teamwork/teamwork_challenger_e2e_m1_2/handoff.md.
