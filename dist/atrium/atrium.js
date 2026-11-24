import { AtriumErrorCard as e, AtriumNoAccess as t, atriumStatus as n, defineAtriumModule as r, integrationData as i, mergeAtriumMessages as a, useAtriumI18n as o, useAtriumIntegration as s, useIntegrationQuery as c } from "@refactor-circus/atrium";
import { UBadge as l, UCard as u, UProgress as d, USkeleton as f } from "@refactor-circus/atrium/ui";
import { Fragment as p, computed as m, createBlock as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, onMounted as S, openBlock as C, renderList as w, resolveComponent as T, shallowRef as E, toDisplayString as D, unref as O, withCtx as k } from "vue";
import { useI18n as A } from "vue-i18n";
// src/components/search/source.ts
function j(e, t, n) {
	let r = (e) => encodeURIComponent(e);
	switch (e) {
		case "products": return `/showroom/products/${r(t)}`;
		case "owners": return `/showroom/owners/${r(t)}`;
		case "attribute_groups": return `/showroom/attribute-groups/${r(n)}`;
		default: return `/showroom/${e}/${r(n)}`;
	}
}
var M = async (e, { limit: t }) => {
	let { client: n } = s("showroom");
	if (!n) return [];
	let r = o(), { data: a } = i(await n.GET("/search", { params: { query: {
		q: e,
		limit: t
	} } }));
	return a.map((e) => ({
		title: e.label,
		subtitle: e.subtitle,
		icon: null,
		group: r.t(`showroom.search.groups.${e.type}`),
		link: {
			path: j(e.type, e.id, e.code),
			external: !1
		}
	}));
}, N = { "data-testid": "showroom-settings" }, ee = {
	key: 3,
	class: "flex flex-col gap-4"
}, te = { class: "font-medium" }, ne = { class: "space-y-2 text-sm" }, P = ["data-row"], F = { class: "text-muted" }, I = { class: "font-mono tabular-nums" }, L = /* @__PURE__ */ x({
	__name: "SettingsPanel",
	props: { data: {} },
	setup(r) {
		let { t: a } = A(), { client: o } = s("showroom"), c = E(null), l = E(null);
		S(async () => {
			if (o) try {
				c.value = i(await o.GET("/settings-summary")).data;
			} catch (e) {
				l.value = e;
			}
		});
		let d = m(() => n(l.value) === 403), g = m(() => a("showroom.settings.off")), y = m(() => {
			let e = c.value;
			return e ? [{
				key: "api",
				title: a("showroom.settings.api"),
				rows: [
					{
						key: "authorization",
						label: a("showroom.settings.authorization"),
						value: e.authorization ? a("showroom.settings.on") : g.value
					},
					{
						key: "routes_prefix",
						label: a("showroom.settings.routes_prefix"),
						value: e.routes.enabled ? `/${e.routes.prefix}` : g.value
					},
					{
						key: "per_page",
						label: a("showroom.settings.per_page"),
						value: String(e.pagination.per_page)
					},
					{
						key: "max_per_page",
						label: a("showroom.settings.max_per_page"),
						value: String(e.pagination.max_per_page)
					}
				]
			}, {
				key: "mcp",
				title: a("showroom.settings.mcp"),
				rows: [{
					key: "web_transport",
					label: a("showroom.settings.web_transport"),
					value: e.mcp.web.enabled ? `/${e.mcp.web.route}` : g.value
				}, {
					key: "local_transport",
					label: a("showroom.settings.local_transport"),
					value: e.mcp.local.enabled ? e.mcp.local.handle : g.value
				}]
			}] : [];
		});
		return (n, r) => {
			let i = t, o = e, s = f, m = u;
			return C(), _("div", N, [O(d) ? (C(), h(i, { key: 0 })) : O(l) ? (C(), h(o, {
				key: 1,
				message: O(a)("atrium.settings.failed")
			}, null, 8, ["message"])) : O(c) ? (C(), _("div", ee, [(C(!0), _(p, null, w(O(y), (e) => (C(), h(m, {
				key: e.key,
				"data-testid": `showroom-settings-${e.key}`
			}, {
				header: k(() => [v("h3", te, D(e.title), 1)]),
				default: k(() => [v("dl", ne, [(C(!0), _(p, null, w(e.rows, (e) => (C(), _("div", {
					key: e.key,
					class: "flex justify-between gap-3",
					"data-row": e.key
				}, [v("dt", F, D(e.label), 1), v("dd", I, D(e.value), 1)], 8, P))), 128))])]),
				_: 2
			}, 1032, ["data-testid"]))), 128))])) : (C(), h(s, {
				key: 2,
				class: "h-24"
			}))]);
		};
	}
}), R = [
	"draft",
	"in_review",
	"approved",
	"archived"
];
function z(e) {
	switch (e) {
		case "in_review": return "info";
		case "approved": return "success";
		case "draft": return "primary";
		default: return "neutral";
	}
}
function B() {
	return c("showroom", "showroom.stats.show", async (e) => i(await e.GET("/stats")).data);
}
function V(e) {
	return `/showroom/products/${encodeURIComponent(e)}`;
}
function H(e) {
	return `/showroom/product-models/${encodeURIComponent(e)}`;
}
function U(e) {
	if (!e) return null;
	let t = new Date(e);
	return Number.isNaN(t.getTime()) ? e : t.toLocaleString();
}
// src/components/widgets/CompletenessWidget.vue?vue&type=script&setup=true&lang.ts
var W = {
	class: "h-full",
	"data-testid": "showroom-completeness"
}, G = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-completeness-empty"
}, K = {
	key: 1,
	class: "flex flex-col gap-3"
}, q = ["data-slot"], J = { class: "flex items-center justify-between gap-3 text-sm" }, Y = { class: "tabular-nums" }, X = { "data-testid": "showroom-completeness-average" }, Z = { class: "text-muted" }, re = {
	key: 3,
	class: "space-y-2"
}, ie = /* @__PURE__ */ x({
	__name: "CompletenessWidget",
	props: {
		data: {},
		settings: {}
	},
	setup(n) {
		let { t: r } = A(), { data: i, status: a, forbidden: o } = B(), s = m(() => i.value?.completeness ?? null);
		return (n, i) => {
			let c = t, l = e, u = T("RouterLink"), m = d, g = f;
			return C(), _("div", W, [O(o) ? (C(), h(c, {
				key: 0,
				compact: ""
			})) : O(a) === "error" ? (C(), h(l, {
				key: 1,
				message: O(r)("atrium.widget.failed")
			}, null, 8, ["message"])) : O(s) ? (C(), _(p, { key: 2 }, [O(s).length === 0 ? (C(), _("p", G, D(O(r)("showroom.widgets.no_completeness_yet")), 1)) : (C(), _("ul", K, [(C(!0), _(p, null, w(O(s), (e) => (C(), _("li", {
				key: `${e.channel}-${e.locale}`,
				"data-testid": "showroom-completeness-row",
				"data-slot": `${e.channel}-${e.locale}`
			}, [v("div", J, [b(u, {
				class: "font-mono hover:underline",
				to: {
					path: "/showroom/products",
					query: {
						"complete[scope]": e.channel,
						"complete[locale]": e.locale,
						"complete[min]": "100"
					}
				}
			}, {
				default: k(() => [y(D(e.channel) + " · " + D(e.locale), 1)]),
				_: 2
			}, 1032, ["to"]), v("span", Y, [v("span", X, D(e.average) + "%", 1), v("span", Z, " — " + D(O(r)("showroom.widgets.fully_complete", {
				complete: e.complete,
				products: e.products
			})), 1)])]), b(m, {
				class: "mt-1",
				"model-value": e.average,
				max: 100,
				size: "sm"
			}, null, 8, ["model-value"])], 8, q))), 128))]))], 64)) : (C(), _("div", re, [b(g, { class: "h-4 w-2/3" }), b(g, { class: "h-4 w-1/2" })]))]);
		};
	}
}), ae = {
	class: "h-full",
	"data-testid": "showroom-product-status"
}, oe = {
	key: 2,
	class: "flex flex-wrap gap-2"
}, se = { class: "text-sm font-semibold tabular-nums" }, ce = { class: "text-sm font-semibold tabular-nums" }, le = {
	key: 3,
	class: "flex gap-2"
}, ue = /* @__PURE__ */ x({
	__name: "ProductStatusWidget",
	props: {
		data: {},
		settings: {}
	},
	setup(n) {
		let { t: r } = A(), { data: i, status: a, forbidden: o } = B(), s = m(() => i.value?.status ?? null);
		return (n, i) => {
			let c = t, u = e, d = l, m = T("RouterLink"), g = f;
			return C(), _("div", ae, [O(o) ? (C(), h(c, {
				key: 0,
				compact: ""
			})) : O(a) === "error" ? (C(), h(u, {
				key: 1,
				message: O(r)("atrium.widget.failed")
			}, null, 8, ["message"])) : O(s) ? (C(), _("div", oe, [(C(!0), _(p, null, w(O(R), (e) => (C(), h(m, {
				key: e,
				to: {
					path: "/showroom/products",
					query: { status: e }
				},
				class: "flex items-center gap-2 rounded-md border border-default px-3 py-2 hover:bg-elevated",
				"data-testid": "showroom-product-status-count",
				"data-status": e
			}, {
				default: k(() => [b(d, {
					color: O(z)(e),
					variant: "subtle",
					size: "sm"
				}, {
					default: k(() => [y(D(O(r)(`showroom.statuses.${e}`)), 1)]),
					_: 2
				}, 1032, ["color"]), v("span", se, D(O(s).counts[e] ?? 0), 1)]),
				_: 2
			}, 1032, ["to", "data-status"]))), 128)), b(m, {
				to: {
					path: "/showroom/products",
					query: { published: "1" }
				},
				class: "flex items-center gap-2 rounded-md border border-default px-3 py-2 hover:bg-elevated",
				"data-testid": "showroom-product-status-count",
				"data-status": "published"
			}, {
				default: k(() => [b(d, {
					color: "success",
					variant: "subtle",
					size: "sm"
				}, {
					default: k(() => [y(D(O(r)("showroom.widgets.live")), 1)]),
					_: 1
				}), v("span", ce, D(O(s).published), 1)]),
				_: 1
			})])) : (C(), _("div", le, [b(g, { class: "h-9 w-24" }), b(g, { class: "h-9 w-24" })]))]);
		};
	}
}), de = {
	class: "h-full",
	"data-testid": "showroom-recent-changes"
}, fe = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-recent-changes-empty"
}, pe = {
	key: 1,
	class: "flex flex-col divide-y divide-default"
}, me = ["data-version"], he = { class: "flex items-center gap-2" }, ge = { class: "font-mono text-muted" }, _e = { class: "text-right text-muted" }, ve = {
	key: 3,
	class: "space-y-2"
}, ye = /* @__PURE__ */ x({
	__name: "RecentChangesWidget",
	props: {
		data: {},
		settings: {}
	},
	setup(n) {
		let { t: r } = A(), { data: i, status: a, forbidden: o } = B(), s = m(() => i.value?.recent_versions ?? null);
		function c(e) {
			return e.subject.key === null ? null : e.subject.type === "product" ? V(e.subject.id) : H(e.subject.key);
		}
		return (n, i) => {
			let u = t, d = e, m = T("RouterLink"), x = l, S = f;
			return C(), _("div", de, [O(o) ? (C(), h(u, {
				key: 0,
				compact: ""
			})) : O(a) === "error" ? (C(), h(d, {
				key: 1,
				message: O(r)("atrium.widget.failed")
			}, null, 8, ["message"])) : O(s) ? (C(), _(p, { key: 2 }, [O(s).length === 0 ? (C(), _("p", fe, D(O(r)("showroom.widgets.no_changes")), 1)) : (C(), _("ul", pe, [(C(!0), _(p, null, w(O(s), (e) => (C(), _("li", {
				key: `${e.subject.type}-${e.subject.id}-${e.version}`,
				class: "flex items-center justify-between gap-3 py-2 text-sm",
				"data-testid": "showroom-recent-change",
				"data-version": e.version
			}, [v("span", he, [
				c(e) ? (C(), h(m, {
					key: 0,
					class: "font-mono hover:underline",
					to: c(e)
				}, {
					default: k(() => [y(D(e.subject.key), 1)]),
					_: 2
				}, 1032, ["to"])) : g("", !0),
				v("span", ge, "v" + D(e.version), 1),
				b(x, {
					color: "neutral",
					variant: "subtle",
					size: "sm"
				}, {
					default: k(() => [y(D(e.action), 1)]),
					_: 2
				}, 1024)
			]), v("span", _e, [e.author ? (C(), _(p, { key: 0 }, [y(D(O(r)("showroom.widgets.by", { author: String(e.author.id) })) + " · ", 1)], 64)) : g("", !0), y(" " + D(O(U)(e.created_at)), 1)])], 8, me))), 128))]))], 64)) : (C(), _("div", ve, [b(S, { class: "h-4 w-2/3" }), b(S, { class: "h-4 w-1/2" })]))]);
		};
	}
}), be = {
	class: "h-full",
	"data-testid": "showroom-review-queue"
}, xe = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-review-queue-empty"
}, Q = { class: "mb-2 text-sm text-muted" }, Se = { class: "flex flex-col divide-y divide-default" }, Ce = ["data-product"], we = { class: "text-muted" }, Te = {
	key: 3,
	class: "space-y-2"
}, Ee = /* @__PURE__ */ x({
	__name: "ReviewQueueWidget",
	props: {
		data: {},
		settings: {}
	},
	setup(n) {
		let { t: r } = A(), { data: i, status: a, forbidden: o } = B(), s = m(() => i.value?.review_queue ?? null);
		return (n, i) => {
			let c = t, l = e, u = T("RouterLink"), d = f;
			return C(), _("div", be, [O(o) ? (C(), h(c, {
				key: 0,
				compact: ""
			})) : O(a) === "error" ? (C(), h(l, {
				key: 1,
				message: O(r)("atrium.widget.failed")
			}, null, 8, ["message"])) : O(s) ? (C(), _(p, { key: 2 }, [O(s).products.length === 0 ? (C(), _("p", xe, D(O(r)("showroom.widgets.nothing_in_review")), 1)) : (C(), _(p, { key: 1 }, [v("p", Q, [b(u, {
				class: "hover:underline",
				to: {
					path: "/showroom/products",
					query: { status: "in_review" }
				},
				"data-testid": "showroom-review-queue-total"
			}, {
				default: k(() => [y(D(O(r)("showroom.widgets.waiting_review", { count: O(s).total })), 1)]),
				_: 1
			})]), v("ul", Se, [(C(!0), _(p, null, w(O(s).products, (e) => (C(), _("li", {
				key: e.id,
				class: "flex items-center justify-between gap-3 py-2 text-sm",
				"data-testid": "showroom-review-queue-product",
				"data-product": e.identifier
			}, [b(u, {
				class: "font-mono hover:underline",
				to: O(V)(e.id)
			}, {
				default: k(() => [y(D(e.identifier), 1)]),
				_: 2
			}, 1032, ["to"]), v("span", we, D(O(U)(e.updated_at)), 1)], 8, Ce))), 128))])], 64))], 64)) : (C(), _("div", Te, [b(d, { class: "h-4 w-1/3" }), b(d, { class: "h-4 w-2/3" })]))]);
		};
	}
}), De = { showroom: {
	assets: {
		title: "Assets",
		grid: "Grid",
		list: "List",
		upload: "Upload an asset",
		upload_button: "Upload",
		file: "File",
		code_hint: "Optional; taken from the file name when left empty.",
		all_types: "All types",
		types: {
			images: "Images",
			pdf: "PDF",
			videos: "Videos"
		},
		product: "Product",
		empty: "No assets match these filters.",
		size: "Size",
		checksum: "Checksum",
		details: "Details",
		open_file: "Open file",
		replace_file: "Replace file",
		links: "Linked to",
		no_links: "This asset is not linked to anything yet.",
		link_types: {
			product: "Product",
			product_model: "Product model",
			owner: "Owner"
		},
		target: "Target",
		target_hint: "A product identifier, or a product model or owner code.",
		role: "Role",
		link: "Link",
		unlink: "Unlink",
		confirm_delete: "Delete this asset? Its links are removed too."
	},
	owners: {
		title: "Owners",
		description: "Brands, suppliers, stores and the other owners products belong to, as a tree.",
		flags: "Flags",
		new: "New owner",
		no_types: "Create an owner type first.",
		code_hint: "Lowercase letters, digits, dashes and underscores. Cannot be changed later.",
		choose_type: "Choose a type",
		parent_hint: "The parent owner's code; leave empty for a root.",
		all_types: "All types",
		empty: "No owners match these filters.",
		children: "Children",
		no_children: "No owners beneath this one yet.",
		add_child: "Add child",
		details: "Details",
		move_hint: "Change to move this owner, with everything beneath it. Empty to make it a root.",
		products: "Products",
		counts: "Owns {products} product(s) and {models} product model(s).",
		no_flags: "No flags apply to owners.",
		confirm_delete: "Delete this owner?"
	},
	owner_types: {
		title: "Owner types",
		description: "The kinds of owner, and the chain rules between them.",
		new: "New owner type",
		code_hint: "Lowercase letters, digits and underscores. Cannot be changed later.",
		empty: "No owner types yet.",
		rules: "Chain rules",
		parent_types: "Parent types",
		any_parent: "Any type may be the parent",
		any: "Any",
		no_types: "No owner types yet.",
		can_be_root: "May be a root",
		owns_products: "Owns products",
		owners: "Owners",
		owners_of_type: "Owners ({count})",
		confirm_delete: "Delete this owner type?"
	},
	transfers: {
		title: "Transfers",
		recent: "Recent transfers",
		flow: "Flow",
		status: "Status",
		started: "Started",
		finished: "Finished",
		no_runs: "No transfers yet.",
		runs_failed: "The transfers could not be loaded from Impex.",
		requires_impex: "Listing transfers requires Impex.",
		requires_impex_hint: "Imports and exports still start here; follow them once Impex is active.",
		started_notice: "Transfer queued ({flow}).",
		import: {
			title: "Import products",
			file: "File",
			file_hint: "A CSV or JSON Lines file.",
			mode: "Mode",
			modes: {
				upsert: "Create or update",
				create: "Create only",
				update: "Update only"
			},
			submit: "Start import"
		},
		export: {
			title: "Export products",
			format: "Format",
			formats: {
				jsonl: "JSON Lines",
				csv: "CSV"
			},
			all_families: "All families",
			category_tree: "Category tree",
			all_categories: "All categories",
			no_channel: "No channel",
			published: "Published copies only",
			submit: "Start export"
		}
	}
} }, Oe = { showroom: { catalog: {
	none: "—",
	yes: "Yes",
	no: "No",
	enabled: "Enabled",
	disabled: "Disabled",
	inherited: "inherited",
	add: "Add",
	create: "Create",
	save: "Save",
	delete: "Delete",
	remove: "Remove",
	state: {
		not_found: "Not found.",
		load_failed: "Showroom could not be loaded.",
		action_forbidden: "You may not do this."
	},
	pager: {
		previous: "Previous",
		next: "Next",
		page: "Page {current} of {last}"
	},
	filters: {
		search: "Search",
		filter: "Filter",
		clear: "Clear",
		all_statuses: "All statuses",
		all_families: "All families",
		all_family_variants: "All family variants"
	},
	fields: {
		identifier: "Identifier",
		code: "Code",
		label: "Label",
		label_in: "Label ({locale})",
		family: "Family",
		family_variant: "Family variant",
		parent: "Parent",
		owner: "Owner",
		category: "Category",
		enabled: "Status",
		workflow: "Workflow",
		updated: "Updated",
		slug: "Slug",
		locale: "Locale",
		channel: "Channel",
		type: "Type",
		group: "Group",
		sort_order: "Sort order"
	},
	products: {
		title: "Products",
		new: "New product",
		create: "Create product",
		empty: "No products found.",
		flag_definitions: "Flag definitions",
		identifier_hint: "Unique, and never changed once the product exists.",
		no_family: "No family",
		details: "Details",
		owner_hint: "An owner code; leave empty for none.",
		inherited_owner: "Owner (from its product model)",
		identifiers_and_flags: "Identifiers and flags",
		confirm_delete: "Delete this product? This cannot be undone."
	},
	product_models: {
		title: "Product models",
		new: "New product model",
		empty: "No product models found.",
		choose_family_variant: "Choose a family variant",
		level: "level {level}",
		variant_products: "Variant products",
		sub_models: "Sub-models",
		add_variant: "Add variant",
		add_sub_model: "Add sub-model",
		confirm_delete: "Delete this product model? This cannot be undone."
	},
	families: {
		title: "Families",
		new: "New family",
		create: "Create family",
		empty: "No families found.",
		code_hint: "Lowercase letters, digits and underscores, starting with a letter.",
		attributes_count: "Attributes",
		label_attribute: "Label attribute",
		label_attribute_hint: "The text attribute products of this family are labelled by.",
		no_label_attribute: "No label attribute",
		members: "Family attributes",
		no_attributes: "This family has no attributes yet.",
		required: "Required",
		required_channels: "Required on channels",
		required_channels_hint: "All channels",
		variants: "Family variants",
		level: "Level {level}",
		axes: "Axes",
		attributes: "Attributes",
		create_variant: "Create family variant",
		confirm_delete: "Delete this family? This cannot be undone."
	},
	family_variants: {
		levels: "Levels",
		level: "Level",
		common: "Common",
		confirm_delete: "Delete this family variant? This cannot be undone."
	},
	workflow: {
		title: "Workflow",
		published_version: "Published version",
		not_published: "Not published",
		comment: "Comment",
		completeness: "Completeness",
		no_completeness: "No completeness scores yet.",
		missing: "Missing",
		history: "History",
		no_history: "No history yet.",
		by: "by {author}",
		revert: "Revert",
		confirm_revert: "Revert the product to version {version}?",
		transitions: {
			submit: "Submit for review",
			approve: "Approve",
			reject: "Reject",
			publish: "Publish",
			unpublish: "Unpublish",
			archive: "Archive",
			restore: "Restore"
		}
	},
	values: {
		title: "Values",
		inherited: "Inherited values",
		none_settable: "No attributes can be set here.",
		slot_unavailable: "Add a locale or channel first.",
		switch: "Switch",
		amount: "Amount",
		unit: "Unit",
		add_attribute: "Add attribute",
		choose_attribute: "Choose an attribute"
	},
	categories: {
		title: "Categories",
		hint: "Category codes, separated by commas.",
		inherited: "Inherited categories"
	},
	external_ids: {
		title: "External ids",
		source: "New source",
		source_hint: "Lowercase letters, digits, dashes and underscores.",
		id: "External id",
		synced: "synced {time}",
		never_synced: "never synced"
	},
	flags: {
		own: "Own flags",
		effective: "Effective flags",
		none: "No flags apply to products."
	},
	assets: {
		title: "Assets",
		none: "No assets here yet.",
		upload_and_link: "Upload and link",
		role: "Role",
		upload: "Upload"
	},
	associations: {
		title: "Associations",
		none: "No associations.",
		type: "Association type",
		choose_type: "Choose a type",
		target_kind: "Target kind",
		product: "Product",
		product_model: "Product model",
		target: "Target",
		quantity: "Quantity",
		quantity_hint: "For quantified types.",
		add: "Add",
		remove: "Remove"
	}
} } }, ke = { showroom: { structure: {
	code_hint: "Lowercase letters, digits and underscores. Cannot be changed later.",
	details: "Details",
	attributes: {
		title: "Attributes",
		new: "New attribute",
		create: "Create attribute",
		all_types: "All types",
		all_groups: "All groups",
		empty: "No attributes match these filters.",
		switches: "Switches",
		is_unique: "Unique",
		is_localizable: "Localizable",
		is_scopable: "Scopable",
		type_hint: "Cannot be changed later.",
		choose_type: "Choose a type",
		no_group: "No group",
		settings: "Settings (JSON)",
		settings_hint: "Type-specific validation, such as max_length. Metric attributes need metric_family and default_unit.",
		invalid_settings: "Settings must be a JSON object.",
		options: "Options",
		no_options: "This attribute has no options yet.",
		add_option: "Add option",
		confirm_delete: "Delete this attribute?",
		confirm_delete_option: "Delete the option {option}?"
	},
	groups: {
		title: "Attribute groups",
		new: "New attribute group",
		create: "Create group",
		empty: "No attribute groups yet.",
		attributes_count: "Attributes",
		members: "Attributes in this group",
		no_members: "No attributes in this group yet. An attribute joins a group from its own page.",
		confirm_delete: "Delete this attribute group?"
	},
	association_types: {
		title: "Association types",
		description: "How products relate: cross-sell, accessories, compatible parts (two-way), bundles and kits (quantified).",
		new: "New association type",
		empty: "No association types yet.",
		two_way: "Two-way",
		quantified: "Quantified",
		associations: "Associations",
		confirm_delete: "Delete the association type {code}?"
	},
	categories: {
		title: "Categories",
		description: "Independent category trees: each root category is a tree.",
		new_tree: "New category tree",
		empty: "No category trees yet.",
		children: "Children",
		chain: "Category path",
		branch: "Branch",
		no_branch: "No categories beneath this one yet.",
		add_child: "Add child",
		products: "Products",
		move_hint: "Change to move this category, with everything beneath it. Empty to make it a tree.",
		filed_counts: "{products} product(s) and {models} product model(s) are filed here.",
		confirm_delete: "Delete this category and everything beneath it?"
	},
	channels: {
		title: "Channels",
		description: "Where products are published, in which locales and currencies, and the locales content is written in.",
		new: "New channel",
		empty: "No channels yet.",
		locales: "Locales",
		no_locales: "No locales yet.",
		locale_code_hint: "A language, optionally with a region: en, en_US.",
		add_locale: "Add locale",
		currencies: "Currencies",
		currencies_hint: "ISO codes, separated by commas: USD, EUR.",
		category_tree: "Category tree",
		delete_note: "Deleting a channel also removes every value scoped to it.",
		confirm_delete: "Delete this channel, and every value scoped to it?",
		confirm_delete_locale: "Delete the locale {code}?"
	},
	flags: {
		title: "Flags",
		description: "Commercial markers (restricted, pricing only, clearance) that products and owners carry. An owner's flags reach everything beneath it.",
		new: "New flag",
		empty: "No flags yet.",
		applies_to: "Applies to",
		subject_product: "Products",
		subject_owner: "Owners",
		delete_note: "A flag still assigned to a product or owner cannot be deleted; remove it from them first.",
		confirm_delete: "Delete this flag?"
	}
} } }, Ae = { showroom: {
	statuses: {
		draft: "Draft",
		in_review: "In review",
		approved: "Approved",
		archived: "Archived"
	},
	widgets: {
		live: "Live",
		no_completeness_yet: "No completeness scores yet. They need families with required attributes and at least one channel.",
		fully_complete: "{complete} of {products} complete",
		nothing_in_review: "Nothing is waiting for review.",
		waiting_review: "{count} waiting",
		no_changes: "No changes yet.",
		by: "by {author}"
	},
	settings: {
		api: "HTTP API",
		mcp: "MCP",
		authorization: "Authorization",
		routes_prefix: "Route prefix",
		per_page: "Page size",
		max_per_page: "Maximum page size",
		web_transport: "Web transport",
		local_transport: "Local transport",
		on: "On",
		off: "Off"
	},
	search: { groups: {
		products: "Products",
		assets: "Assets",
		categories: "Categories",
		owners: "Owners",
		families: "Families",
		attributes: "Attributes",
		attribute_groups: "Attribute groups"
	} }
} }, je = [
	{
		path: "/showroom/assets/:asset",
		name: "showroom-assets-asset",
		component: () => import("./chunks/_asset_-DR6XVmhe.js")
	},
	{
		path: "/showroom/assets",
		name: "showroom-assets",
		component: () => import("./chunks/assets-uhYkcx8t.js")
	},
	{
		path: "/showroom/association-types",
		name: "showroom-association-types",
		component: () => import("./chunks/association-types-DTPIV6QU.js")
	},
	{
		path: "/showroom/attribute-groups/:group",
		name: "showroom-attribute-groups-group",
		component: () => import("./chunks/_group_-SgppfZbC.js")
	},
	{
		path: "/showroom/attribute-groups",
		name: "showroom-attribute-groups",
		component: () => import("./chunks/attribute-groups-DUjLdRxf.js")
	},
	{
		path: "/showroom/attributes/:attribute",
		name: "showroom-attributes-attribute",
		component: () => import("./chunks/_attribute_--TXN91U1.js")
	},
	{
		path: "/showroom/attributes/create",
		name: "showroom-attributes-create",
		component: () => import("./chunks/create-CmQY3EeL.js")
	},
	{
		path: "/showroom/attributes",
		name: "showroom-attributes",
		component: () => import("./chunks/attributes-DWBRCaOt.js")
	},
	{
		path: "/showroom/categories/:category",
		name: "showroom-categories-category",
		component: () => import("./chunks/_category_-C37u8ksX.js")
	},
	{
		path: "/showroom/categories",
		name: "showroom-categories",
		component: () => import("./chunks/categories-ByEVStEG.js")
	},
	{
		path: "/showroom/channels/:channel",
		name: "showroom-channels-channel",
		component: () => import("./chunks/_channel_-DJ3yfAz7.js")
	},
	{
		path: "/showroom/channels",
		name: "showroom-channels",
		component: () => import("./chunks/channels-D2n0YhuZ.js")
	},
	{
		path: "/showroom/families/:family",
		name: "showroom-families-family",
		component: () => import("./chunks/_family_-B9IU_OP2.js")
	},
	{
		path: "/showroom/families",
		name: "showroom-families",
		component: () => import("./chunks/families-DYjHSM1F.js")
	},
	{
		path: "/showroom/family-variants/:familyVariant",
		name: "showroom-family-variants-familyVariant",
		component: () => import("./chunks/_familyVariant_-4vrTesnV.js")
	},
	{
		path: "/showroom/flags/:flag",
		name: "showroom-flags-flag",
		component: () => import("./chunks/_flag_-BW9e6wB8.js")
	},
	{
		path: "/showroom/flags",
		name: "showroom-flags",
		component: () => import("./chunks/flags-lpke_po8.js")
	},
	{
		path: "/showroom/owner-types/:ownerType",
		name: "showroom-owner-types-ownerType",
		component: () => import("./chunks/_ownerType_-DD1MO30y.js")
	},
	{
		path: "/showroom/owner-types",
		name: "showroom-owner-types",
		component: () => import("./chunks/owner-types-CCEaR62f.js")
	},
	{
		path: "/showroom/owners/:owner",
		name: "showroom-owners-owner",
		component: () => import("./chunks/_owner_-QLqC4Y3m.js")
	},
	{
		path: "/showroom/owners",
		name: "showroom-owners",
		component: () => import("./chunks/owners-CMRvxRVE.js")
	},
	{
		path: "/showroom/product-models/:productModel",
		name: "showroom-product-models-productModel",
		component: () => import("./chunks/_productModel_-RhzqWtAE.js")
	},
	{
		path: "/showroom/product-models",
		name: "showroom-product-models",
		component: () => import("./chunks/product-models-COK93XqA.js")
	},
	{
		path: "/showroom/products/:product",
		name: "showroom-products-product",
		component: () => import("./chunks/_product_-DbnHx7hU.js")
	},
	{
		path: "/showroom/products/create",
		name: "showroom-products-create",
		component: () => import("./chunks/create-B5c-zBKN.js")
	},
	{
		path: "/showroom/products",
		name: "showroom-products",
		component: () => import("./chunks/products-YPz21AbX.js")
	},
	{
		path: "/showroom/transfers",
		name: "showroom-transfers",
		component: () => import("./chunks/transfers-BCKlDr0I.js")
	}
], $ = {
	"showroom.products": "showroom-products",
	"showroom.product-create": "showroom-products-create",
	"showroom.product": "showroom-products-product",
	"showroom.product-models": "showroom-product-models",
	"showroom.product-model": "showroom-product-models-productModel",
	"showroom.families": "showroom-families",
	"showroom.family": "showroom-families-family",
	"showroom.family-variant": "showroom-family-variants-familyVariant",
	"showroom.attributes": "showroom-attributes",
	"showroom.attribute-create": "showroom-attributes-create",
	"showroom.attribute": "showroom-attributes-attribute",
	"showroom.attribute-groups": "showroom-attribute-groups",
	"showroom.attribute-group": "showroom-attribute-groups-group",
	"showroom.association-types": "showroom-association-types",
	"showroom.categories": "showroom-categories",
	"showroom.category": "showroom-categories-category",
	"showroom.channels": "showroom-channels",
	"showroom.channel": "showroom-channels-channel",
	"showroom.owners": "showroom-owners",
	"showroom.owner": "showroom-owners-owner",
	"showroom.owner-types": "showroom-owner-types",
	"showroom.owner-type": "showroom-owner-types-ownerType",
	"showroom.flags": "showroom-flags",
	"showroom.flag": "showroom-flags-flag",
	"showroom.assets": "showroom-assets",
	"showroom.asset": "showroom-assets-asset",
	"showroom.transfers": "showroom-transfers"
}, Me = r({
	key: "showroom",
	routes: je,
	screens: $,
	widgets: {
		"showroom.product-status": ue,
		"showroom.completeness": ie,
		"showroom.review-queue": Ee,
		"showroom.recent-changes": ye
	},
	settingsPanels: { showroom: L },
	searchSources: { showroom: M },
	messages: { en: a(De, Oe, ke, Ae) }
});
export { Me as default, $ as screens };
