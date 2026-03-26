import http from "http";
import fs from "fs";
import jwt from "jsonwebtoken";

const JWT_SECRET = "SUPER_RANDOM_SECRET_983274982374"; // same as PHP

function findUser(username, password) {
  // Read users.txt in the same folder as this file
  const data = fs.readFileSync("./users.txt", "utf8");
  const lines = data.split("\n").map((l) => l.trim()).filter(Boolean);

  for (const line of lines) {
    const [u, p, userId, role] = line.split(",");
    if (u === username && p === password) {
      return { userId: Number(userId), role };
    }
  }
  return null;
}

http
  .createServer((req, res) => {
    // Only handle POST /login
    if (req.method === "POST" && req.url === "/login") {
      let body = "";
      req.on("data", (chunk) => {
        body += chunk;
      });
      req.on("end", () => {
        try {
          const data = JSON.parse(body);
          const { username, password } = data;

          if (!username || !password) {
            res.writeHead(400, { "Content-Type": "application/json" });
            return res.end(JSON.stringify({ error: "Missing username or password" }));
          }

          const user = findUser(username, password);

          if (!user) {
            res.writeHead(401, { "Content-Type": "application/json" });
            return res.end(JSON.stringify({ error: "Invalid credentials" }));
          }

          const token = jwt.sign(
            { userId: user.userId, role: user.role },
            JWT_SECRET,
            { expiresIn: "1h" }
          );

          res.writeHead(200, { "Content-Type": "application/json" });
          res.end(JSON.stringify({ token }));
        } catch (err) {
          console.error(err);
          res.writeHead(500, { "Content-Type": "text/plain" });
          res.end("Server error\n");
        }
      });

      return;
    }

    // Everything else → 404
    res.writeHead(404, { "Content-Type": "text/plain" });
    res.end("Not found\n");
  })
  .listen(8000);

console.log("Node auth service listening on port 8000");
