import jwt from "jsonwebtoken";

const JWT_SECRET = process.env.JWT_SECRET || "super-secret-change-me-in-production";

export function signToken(user) {
  return jwt.sign(
    { userid: user.userid, username: user.username, role: user.role },
    JWT_SECRET,
    { expiresIn: "12h" }
  );
}

export function getTokenUser(token) {
  try {
    return jwt.verify(token, JWT_SECRET);
  } catch {
    return null;
  }
}

export function requireAuth(req, res, next) {
  const header = req.headers.authorization || "";
  const token = header.startsWith("Bearer ") ? header.slice(7) : null;
  const payload = token ? getTokenUser(token) : null;
  if (!payload) {
    return res.status(401).json({ error: "Unauthorized" });
  }
  req.user = payload;
  next();
}

export function requireAdmin(req, res, next) {
  if (!req.user || req.user.role !== "Admin") {
    return res.status(403).json({ error: "Admin access required" });
  }
  next();
}

export { JWT_SECRET };